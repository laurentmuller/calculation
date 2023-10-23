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
use App\Model\CalculationUpdateQuery;
use App\Model\CalculationUpdateResult;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Traits\LoggerAwareTrait;
use App\Traits\SessionAwareTrait;
use App\Traits\TranslatorAwareTrait;
use App\Utils\DateUtils;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Service to update overall total of calculations.
 */
class CalculationUpdateService implements ServiceSubscriberInterface
{
    use LoggerAwareTrait;
    use ServiceSubscriberTrait;
    use SessionAwareTrait;
    use TranslatorAwareTrait;

    private const KEY_DATE_FROM = 'calculation.update.date_from';
    private const KEY_DATE_TO = 'calculation.update.date_to';
    private const KEY_STATES = 'calculation.update.states';

    public function __construct(
        private readonly CalculationRepository $calculationRepository,
        private readonly CalculationStateRepository $stateRepository,
        private readonly SuspendEventListenerService $listenerService,
        private readonly CalculationService $calculationService,
    ) {
    }

    public function createQuery(): CalculationUpdateQuery
    {
        $query = new CalculationUpdateQuery();
        $query->setDateFrom($this->getDate(self::KEY_DATE_FROM, $query->getDateFrom()))
            ->setDateTo($this->getDate(self::KEY_DATE_TO, $query->getDateTo()))
            ->setStates($this->getStates(true));

        return $query;
    }

    public function saveQuery(CalculationUpdateQuery $query): void
    {
        $this->setSessionValues([
            self::KEY_DATE_FROM => $query->getDateFrom(),
            self::KEY_DATE_TO => $query->getDateTo(),
            self::KEY_STATES => $query->getStatesId(),
        ]);
    }

    /**
     * @throws ORMException
     */
    public function update(CalculationUpdateQuery $query): CalculationUpdateResult
    {
        $result = new CalculationUpdateResult();
        if ([] === $query->getStates()) {
            return $result;
        }

        /** @psalm-var Calculation[] $calculations */
        $calculations = $this->getCalculations($query);
        if ([] === $calculations) {
            return $result;
        }

        foreach ($calculations as $calculation) {
            $oldTotal = $calculation->getOverallTotal();
            if (!$this->calculationService->updateTotal($calculation)) {
                continue;
            }
            $newTotal = $calculation->getOverallTotal();
            if ($oldTotal === $newTotal) {
                continue;
            }
            $result->addCalculation($oldTotal, $calculation);
        }

        if ($query->isSimulate() || !$result->isValid()) {
            return $result;
        }

        $this->listenerService->suspendListeners(function () use ($query, $result): void {
            $this->calculationRepository->flush();
            $this->logResult($query, $result);
        });

        return $result;
    }

    private function getCalculations(CalculationUpdateQuery $query): array
    {
        return $this->calculationRepository
            ->createQueryBuilder('c')
            ->where('c.state in (:states)')
            ->andWhere('c.date >= :from')
            ->andWhere('c.date <= :to')
            ->setParameter('states', $query->getStates())
            ->setParameter('from', $query->getDateFrom(), Types::DATE_MUTABLE)
            ->setParameter('to', $query->getDateTo(), Types::DATE_MUTABLE)
            ->getQuery()
            ->getResult();
    }

    private function getDate(string $key, \DateTimeInterface $default): \DateTimeInterface
    {
        $date = $this->getSessionDate($key, $default);
        if ($date instanceof \DateTime) {
            return DateUtils::removeTime($date);
        }

        return $date;
    }

    /**
     * @return CalculationState[]
     */
    private function getStates(bool $useSession): array
    {
        /** @var CalculationState[] $sources */
        $sources = $this->stateRepository
            ->getEditableQueryBuilder()
            ->getQuery()
            ->getResult();
        if ($useSession) {
            /** @var int[] $ids */
            $ids = $this->getSessionValue(self::KEY_STATES, []);
            if ([] !== $ids) {
                return \array_filter($sources, fn (CalculationState $state): bool => \in_array($state->getId(), $ids, true));
            }
        }

        return $sources;
    }

    private function logResult(CalculationUpdateQuery $query, CalculationUpdateResult $result): void
    {
        $context = [
            $this->trans('calculation.update.dateFrom') => $query->getDateFromFormatted(),
            $this->trans('calculation.update.dateTo') => $query->getDateToFormatted(),
            $this->trans('calculation.update.states') => $query->getStatesCode(),
            $this->trans('calculation.list.title') => $result->count(),
        ];
        $message = $this->trans('calculation.update.title');
        $this->logInfo($message, $context);
    }
}
