<?php

/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Service;

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Model\CalculationArchiveQuery;
use App\Model\CalculationArchiveResult;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Traits\LoggerAwareTrait;
use App\Traits\SessionAwareTrait;
use App\Traits\TranslatorAwareTrait;
use App\Utils\DateUtils;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Types\DatePointType;
use Symfony\Component\Clock\DatePoint;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Service to archive calculations.
 */
class CalculationArchiveService implements ServiceSubscriberInterface
{
    use LoggerAwareTrait;
    use ServiceMethodsSubscriberTrait;
    use SessionAwareTrait;
    use TranslatorAwareTrait;

    private const KEY_DATE = 'archive.date';
    private const KEY_SOURCES = 'archive.sources';
    private const KEY_TARGET = 'archive.target';

    public function __construct(
        private readonly CalculationRepository $calculationRepository,
        private readonly CalculationStateRepository $stateRepository,
        private readonly SuspendEventListenerService $service,
    ) {
    }

    /**
     * Create the archive query.
     */
    public function createQuery(): CalculationArchiveQuery
    {
        $query = new CalculationArchiveQuery();
        $query->setSources($this->getSources(true))
            ->setTarget($this->getTarget())
            ->setDate($this->getDate());

        return $query;
    }

    /**
     * Gets the maximum allowed date or null if none.
     */
    public function getDateMaxConstraint(): ?string
    {
        $sources = $this->getSources(false);
        $date = $this->getDateMax($sources);

        return $date instanceof DatePoint ? DateUtils::formatFormDate(DateUtils::sub($date, 'P1M')) : null;
    }

    /**
     * Gets the minimum allowed date or null if none.
     */
    public function getDateMinConstraint(): ?string
    {
        $sources = $this->getSources(false);
        $date = $this->getDateMin($sources);

        return $date instanceof DatePoint ? DateUtils::formatFormDate($date) : null;
    }

    /**
     * Returns a value indicating if one or more calculation states are editable.
     */
    public function isEditableStates(): bool
    {
        return $this->stateRepository->getEditableCount() > 0;
    }

    /**
     * Returns a value indicating if one or more calculation states are not editable.
     */
    public function isNotEditableStates(): bool
    {
        return $this->stateRepository->getNotEditableCount() > 0;
    }

    /**
     * Save the query values to the session.
     */
    public function saveQuery(CalculationArchiveQuery $query): void
    {
        $date = $query->isSimulate() ? $query->getDate()->getTimestamp() : null;
        $this->setSessionValues([
            self::KEY_SOURCES => $query->getSourcesId(),
            self::KEY_TARGET => $query->getTargetId(),
            self::KEY_DATE => $date,
        ]);
    }

    /**
     * Update the calculations.
     */
    public function update(CalculationArchiveQuery $query): CalculationArchiveResult
    {
        $target = $query->getTarget();
        $sources = $query->getSources();
        $date = $query->getDate();

        $result = new CalculationArchiveResult();
        $calculations = $this->getCalculations($date, $sources);
        foreach ($calculations as $calculation) {
            $oldState = $calculation->getState();
            if ($oldState instanceof CalculationState && $oldState !== $target) {
                $calculation->setState($target);
                $result->addCalculation($oldState, $calculation);
            }
        }

        if ($query->isSimulate() || !$result->isValid()) {
            return $result;
        }

        $this->service->suspendListeners(fn () => $this->calculationRepository->flush());
        $this->logResult($query, $result);

        return $result;
    }

    /**
     * @param CalculationState[] $sources
     */
    private function createQueryBuilder(array $sources, ?DatePoint $date = null): QueryBuilder
    {
        $builder = $this->calculationRepository
            ->createQueryBuilder('c');
        if ([] !== $sources) {
            $builder->andWhere('c.state IN (:states)')
                ->setParameter('states', $sources);
        }
        if ($date instanceof DatePoint) {
            $builder->andWhere('c.date <= :date')
                ->setParameter('date', $date, DatePointType::NAME);
        }

        return $builder;
    }

    /**
     * Gets the calculations to archive.
     *
     * @param CalculationState[] $sources
     *
     * @return Calculation[]
     */
    private function getCalculations(DatePoint $date, array $sources): array
    {
        if ([] === $sources) {
            return [];
        }

        /** @var Calculation[] */
        return $this->createQueryBuilder($sources, $date)
            ->getQuery()
            ->getResult();
    }

    private function getDate(): DatePoint
    {
        $date = $this->getSessionDate(self::KEY_DATE);
        if ($date instanceof DatePoint) {
            return $date;
        }

        $sources = $this->getSources(false);
        $minDate = $this->getDateMin($sources);
        if (!$minDate instanceof DatePoint) {
            return DateUtils::sub(DateUtils::createDatePoint(), 'P6M');
        }

        $minDate = DateUtils::add($minDate, 'P1M');
        $maxDate = $this->getDateMax($sources);
        if ($maxDate instanceof DatePoint && $minDate >= $maxDate) {
            return DateUtils::sub($maxDate, 'P1M');
        }

        return $minDate;
    }

    /**
     * @param CalculationState[] $sources
     */
    private function getDateMax(array $sources): ?DatePoint
    {
        return $this->getScalarDate($sources, 'MAX');
    }

    /**
     * @param CalculationState[] $sources
     */
    private function getDateMin(array $sources): ?DatePoint
    {
        return $this->getScalarDate($sources, 'MIN');
    }

    /**
     * @param CalculationState[] $sources
     */
    private function getScalarDate(array $sources, string $function): ?DatePoint
    {
        $builder = $this->createQueryBuilder($sources)
            ->select(\sprintf('%s(c.date)', $function));

        /** @var string|null $date */
        $date = $builder->getQuery()->getSingleScalarResult();

        return null === $date ? null : DateUtils::createDatePoint($date);
    }

    /**
     * @return CalculationState[]
     */
    private function getSources(bool $useSession): array
    {
        /** @var CalculationState[] $sources */
        $sources = $this->stateRepository
            ->getEditableQueryBuilder()
            ->getQuery()
            ->getResult();

        if (!$useSession) {
            return $sources;
        }

        /** @var int[] $ids */
        $ids = $this->getSessionValue(self::KEY_SOURCES, []);
        if ([] === $ids) {
            return $sources;
        }

        return \array_filter($sources, static fn (CalculationState $state): bool => \in_array($state->getId(), $ids, true));
    }

    private function getTarget(): ?CalculationState
    {
        $id = $this->getSessionInt(self::KEY_TARGET);
        if (0 !== $id) {
            return $this->stateRepository->find($id);
        }

        return null;
    }

    private function logResult(CalculationArchiveQuery $query, CalculationArchiveResult $result): void
    {
        $context = [
            $this->trans('archive.fields.date') => $query->getDateFormatted(),
            $this->trans('archive.fields.sources') => $query->getSourcesCode(),
            $this->trans('archive.fields.target') => $query->getTargetCode(),
            $this->trans('archive.result.calculations') => $result->count(),
        ];
        $message = $this->trans('counters.calculations_archive', ['count' => $result->count()]);
        $this->logInfo($message, $context);
    }
}
