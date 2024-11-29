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
use App\Utils\FormatUtils;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Service to update the overall total of calculations.
 */
class CalculationUpdateService implements ServiceSubscriberInterface
{
    use LoggerAwareTrait;
    use ServiceMethodsSubscriberTrait;
    use SessionAwareTrait;
    use TranslatorAwareTrait;
    private const KEY_DATE = 'calculation.update.date';
    private const KEY_INTERVAL = 'calculation.update.interval';
    private const KEY_STATES = 'calculation.update.states';

    public function __construct(
        private readonly CalculationRepository $calculationRepository,
        private readonly CalculationStateRepository $stateRepository,
        private readonly SuspendEventListenerService $listenerService,
        private readonly CalculationService $calculationService,
    ) {
    }

    /**
     * @throws ORMException
     */
    public function createQuery(): CalculationUpdateQuery
    {
        $query = new CalculationUpdateQuery();
        $query->setDate($this->getDate($query->getDate()))
            ->setInterval($this->getInterval($query->getInterval()))
            ->setStates($this->getStates(true));

        return $query;
    }

    public function saveQuery(CalculationUpdateQuery $query): void
    {
        $this->setSessionValues([
            self::KEY_DATE => $query->getDate(),
            self::KEY_INTERVAL => $query->getInterval(),
            self::KEY_STATES => $query->getStatesId(),
        ]);
    }

    /**
     * @throws ORMException|\Exception
     */
    public function update(CalculationUpdateQuery $query): CalculationUpdateResult
    {
        $result = new CalculationUpdateResult();
        if ([] === $query->getStates()) {
            return $result;
        }

        $calculations = $this->getCalculations($query);
        if ([] === $calculations) {
            return $result;
        }

        foreach ($calculations as $calculation) {
            $oldTotal = $calculation->getOverallTotal();
            if (!$this->calculationService->updateTotal($calculation)) {
                continue;
            }
            $result->addCalculation($oldTotal, $calculation);
        }

        if ($query->isSimulate() || !$result->isValid()) {
            return $result;
        }

        $this->listenerService->suspendListeners(fn () => $this->calculationRepository->flush());
        $this->logResult($query, $result);

        return $result;
    }

    /**
     * @psalm-return Calculation[]
     *
     * @throws \Exception
     */
    private function getCalculations(CalculationUpdateQuery $query): array
    {
        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->in('state', $query->getStates()))
            ->andWhere(Criteria::expr()->gte('date', $query->getDateFrom()))
            ->andWhere(Criteria::expr()->lte('date', $query->getDate()));

        /** @psalm-var Calculation[] */
        return $this->calculationRepository
            ->createQueryBuilder('c')
            ->addCriteria($criteria)
            ->getQuery()
            ->getResult();
    }

    private function getDate(\DateTimeImmutable $default): \DateTimeImmutable
    {
        $date = $this->getSessionDate(self::KEY_DATE, $default);
        if ($date instanceof \DateTimeImmutable) {
            return $date;
        }
        $date = DateUtils::removeTime($date);

        return DateUtils::toDateTimeImmutable($date);
    }

    private function getInterval(string $default): string
    {
        return $this->getSessionString(self::KEY_INTERVAL, $default);
    }

    /**
     * @return CalculationState[]
     *
     * @throws ORMException
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

    /**
     * @throws \Exception
     */
    private function logResult(CalculationUpdateQuery $query, CalculationUpdateResult $result): void
    {
        $context = [
            $this->trans('calculation.update.dateFrom') => FormatUtils::formatDate($query->getDateFrom()),
            $this->trans('calculation.update.dateTo') => FormatUtils::formatDate($query->getDate()),
            $this->trans('calculation.update.states') => $query->getStatesCode(),
            $this->trans('calculation.list.title') => $result->count(),
        ];
        $message = $this->trans('calculation.update.title');
        $this->logInfo($message, $context);
    }
}
