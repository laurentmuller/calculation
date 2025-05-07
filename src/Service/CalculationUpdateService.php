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
use App\Repository\GlobalMarginRepository;
use App\Traits\LoggerAwareTrait;
use App\Traits\MathTrait;
use App\Traits\SessionAwareTrait;
use App\Traits\TranslatorAwareTrait;
use App\Utils\FormatUtils;
use Doctrine\Common\Collections\Criteria;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Service to update the overall total of calculations.
 */
class CalculationUpdateService implements ServiceSubscriberInterface
{
    use LoggerAwareTrait;
    use MathTrait;
    use ServiceMethodsSubscriberTrait;
    use SessionAwareTrait;
    use TranslatorAwareTrait;

    private const KEY_DATE = 'calculation.update.date';
    private const KEY_INTERVAL = 'calculation.update.interval';
    private const KEY_STATES = 'calculation.update.states';

    public function __construct(
        private readonly GlobalMarginRepository $globalMarginRepository,
        private readonly CalculationRepository $calculationRepository,
        private readonly CalculationStateRepository $stateRepository,
        private readonly SuspendEventListenerService $listenerService
    ) {
    }

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
     * @throws \Exception
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
            if (!$this->updateCalculation($calculation)) {
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
     * Update the given calculation's total.
     *
     * @return bool true if updated; false otherwise
     */
    public function updateCalculation(Calculation $calculation): bool
    {
        // save old values
        $old_items_total = $this->round($calculation->getItemsTotal());
        $old_overall_total = $this->round($calculation->getOverallTotal());
        $old_global_margin = $this->round($calculation->getGlobalMargin());

        // 1. update each group and compute item and overall total
        $items_total = 0.0;
        $overall_total = 0.0;
        $groups = $calculation->getGroups();
        foreach ($groups as $group) {
            $group->update();
            $items_total += $group->getAmount();
            $overall_total += $group->getTotal();
        }
        $items_total = $this->round($items_total);
        $overall_total = $this->round($overall_total);

        // 2. update global margin, net total and overall total
        $global_margin = $this->round($this->getGlobalMargin($overall_total));
        $overall_total = $this->round($overall_total * $global_margin);
        $overall_total = $this->round($overall_total * (1.0 + $calculation->getUserMargin()));

        // 3. equal?
        if ($this->isFloatEquals($old_items_total, $items_total)
            && $this->isFloatEquals($old_global_margin, $global_margin)
            && $this->isFloatEquals($old_overall_total, $overall_total)) {
            return false;
        }

        // 4. update
        $calculation->setItemsTotal($items_total)
            ->setGlobalMargin($global_margin)
            ->setOverallTotal($overall_total);

        return true;
    }

    /**
     * @phpstan-return Calculation[]
     *
     * @throws \Exception
     */
    private function getCalculations(CalculationUpdateQuery $query): array
    {
        $expr = Criteria::expr();
        $criteria = Criteria::create()
            ->andWhere($expr->in('state', $query->getStates()))
            ->andWhere($expr->gte('date', $query->getDateFrom()))
            ->andWhere($expr->lte('date', $query->getDate()));

        /** @phpstan-var Calculation[] */
        return $this->calculationRepository
            ->createQueryBuilder('c')
            ->addCriteria($criteria)
            ->getQuery()
            ->getResult();
    }

    private function getDate(\DateTimeImmutable $default): \DateTimeImmutable
    {
        $date = $this->getSessionDate(self::KEY_DATE, $default);

        return $date instanceof \DateTimeImmutable ? $date : $default;
    }

    /**
     * Gets the global margin, in percent, for the given amount.
     */
    private function getGlobalMargin(float $amount): float
    {
        return $this->isFloatZero($amount) ? 0.0 : $this->globalMarginRepository->getMargin($amount);
    }

    private function getInterval(string $default): string
    {
        return $this->getSessionString(self::KEY_INTERVAL, $default);
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
