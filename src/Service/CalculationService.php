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
use App\Entity\CalculationGroup;
use App\Interfaces\ConstantsInterface;
use App\Model\CalculationAdjustQuery;
use App\Model\CalculationAdjustResult;
use App\Model\CalculationQueryGroup;
use App\Model\CalculationResultGroup;
use App\Parameter\ApplicationParameters;
use App\Repository\GlobalMarginRepository;
use App\Repository\GroupMarginRepository;
use App\Repository\GroupRepository;
use App\Traits\MathTrait;
use App\Utils\StringUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to create total groups and update user margin to meet the minimum overall margin.
 */
class CalculationService implements ConstantsInterface
{
    use MathTrait;

    /**
     * Empty row identifier.
     */
    private const ROW_EMPTY = -1;

    /**
     * Global margin row identifier.
     */
    private const ROW_GLOBAL_MARGIN = -4;

    /**
     * Group row identifier.
     */
    private const ROW_GROUP = -2;

    /**
     * Overall total row identifier.
     */
    private const ROW_OVERALL_TOTAL = -7;

    /**
     * Total group row identifier.
     */
    private const ROW_TOTAL_GROUP = -3;

    /**
     * Total net row identifier.
     */
    private const ROW_TOTAL_NET = -5;

    /**
     * User margin row identifier.
     */
    private const ROW_USER_MARGIN = -6;

    public function __construct(
        private readonly GlobalMarginRepository $globalMarginRepository,
        private readonly GroupMarginRepository $groupMarginRepository,
        private readonly GroupRepository $groupRepository,
        private readonly ApplicationParameters $parameters,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Gets the row constants.
     *
     * @return array<string, int>
     */
    #[\Override]
    public static function constants(): array
    {
        $reflection = new \ReflectionClass(self::class);
        $constants = $reflection->getReflectionConstants(\ReflectionClassConstant::IS_PRIVATE);

        return \array_reduce(
            $constants,
            static fn (array $carry, \ReflectionClassConstant $c): array => $carry + [$c->getName() => $c->getValue()],
            []
        );
    }

    /**
     * Creates the total groups for the given calculation.
     *
     * @return Collection<int, CalculationResultGroup>
     */
    public function createGroups(Calculation $calculation): Collection
    {
        if ($calculation->isEmpty()) {
            return $this->createEmptyGroups();
        }

        $groups = $calculation->getGroups()
            ->map(fn (CalculationGroup $group): CalculationResultGroup => $this->createResultGroup(
                id: self::ROW_GROUP,
                description: $group->getCode(),
                marginPercent: $group->getMargin(),
                marginAmount: $group->getMarginAmount(),
                amount: $group->getAmount(),
                total: $group->getTotal(),
            ));
        $this->addTotalGroups($groups, $calculation->getUserMargin(), $calculation->getGlobalMargin());

        return $groups;
    }

    /**
     * Create parameters to render the total view.
     */
    public function createParameters(CalculationAdjustQuery $query): CalculationAdjustResult
    {
        if ([] === $query->groups) {
            return $this->createEmptyParameters();
        }

        $groups = $this->convertQueryGroups($query->groups);
        if ($groups->isEmpty()) {
            return $this->createEmptyParameters();
        }

        $userMargin = $query->userMargin;
        $this->addTotalGroups($groups, $userMargin);
        $overallGroup = $this->getGroup($groups, self::ROW_OVERALL_TOTAL);
        $overallTotal = $overallGroup->total;
        $overallMargin = $overallGroup->marginPercent;
        $overallBelow = $this->parameters->isMarginBelow($overallMargin);

        if ($query->adjust && $overallBelow) {
            $this->adjustUserMargin($groups);
            $userMargin = $this->getGroup($groups, self::ROW_USER_MARGIN)->marginPercent;
            $overallBelow = false;
        }

        return new CalculationAdjustResult(
            overallBelow: $overallBelow,
            overallMargin: $overallMargin,
            overallTotal: $overallTotal,
            userMargin: $userMargin,
            minMargin: $this->getMinMargin(),
            groups: $groups,
            adjust: $query->adjust,
        );
    }

    /**
     * @param Collection<int, CalculationResultGroup> $groups
     */
    private function addTotalGroups(Collection $groups, float $userMargin, ?float $globalMargin = null): void
    {
        $groupsAmount = $this->round($this->getGroupsAmount($groups));
        $groupsMargin = $this->round($this->getGroupsMargin($groups));
        $totalNet = $this->round($groupsAmount + $groupsMargin);
        $totalMargin = 1.0 + $this->round($this->safeDivide($groupsMargin, $groupsAmount));
        $groups->set(self::ROW_TOTAL_GROUP, $this->createResultGroup(
            id: self::ROW_TOTAL_GROUP,
            marginPercent: $totalMargin,
            marginAmount: $groupsMargin,
            amount: $groupsAmount,
            total: $totalNet
        ));

        $globalMargin ??= $this->getGlobalMargin($totalNet);
        $globalAmount = $this->round($totalNet * ($globalMargin - 1.0));
        $groups->set(self::ROW_GLOBAL_MARGIN, $this->createResultGroup(
            id: self::ROW_GLOBAL_MARGIN,
            marginPercent: $globalMargin,
            total: $globalAmount
        ));

        $totalNet += $globalAmount;
        $groups->set(self::ROW_TOTAL_NET, $this->createResultGroup(
            id: self::ROW_TOTAL_NET,
            total: $totalNet
        ));

        $userAmount = $this->round($totalNet * $userMargin);
        $groups->set(self::ROW_USER_MARGIN, $this->createResultGroup(
            id: self::ROW_USER_MARGIN,
            marginPercent: $userMargin,
            total: $userAmount
        ));

        $overallTotal = $totalNet + $userAmount;
        $overallAmount = $this->round($overallTotal - $groupsAmount);
        $overallMargin = $this->floor(1.0 + $this->safeDivide($overallAmount, $groupsAmount));
        $groups->set(self::ROW_OVERALL_TOTAL, $this->createResultGroup(
            id: self::ROW_OVERALL_TOTAL,
            marginPercent: $overallMargin,
            marginAmount: $overallAmount,
            amount: $groupsAmount,
            total: $overallTotal
        ));
    }

    /**
     * @param Collection<int, CalculationResultGroup> $groups
     */
    private function adjustUserMargin(Collection $groups): void
    {
        $totalNet = $this->getGroup($groups, self::ROW_TOTAL_NET)->total;
        $totalGroup = $this->getGroup($groups, self::ROW_TOTAL_GROUP)->amount;

        $userMargin = $this->ceil((($totalGroup * $this->getMinMargin()) - $totalNet) / $totalNet);
        $userTotal = $this->round($totalNet * $userMargin);
        $userMarginGroup = $this->getGroup($groups, self::ROW_USER_MARGIN);
        $userMarginGroup->marginPercent = $userMargin;
        $userMarginGroup->total = $userTotal;

        $overallTotal = $totalNet + $userTotal;
        $overallTotalGroup = $this->getGroup($groups, self::ROW_OVERALL_TOTAL);
        $overallTotalGroup->marginPercent = $this->floor($overallTotal / $totalGroup);
        $overallTotalGroup->marginAmount = $overallTotal - $totalGroup;
        $overallTotalGroup->total = $overallTotal;
    }

    /**
     * @param CalculationQueryGroup[] $queryGroups
     *
     * @return Collection<int, CalculationResultGroup>
     */
    private function convertQueryGroups(array $queryGroups): Collection
    {
        $groups = new ArrayCollection();
        foreach ($queryGroups as $queryGroup) {
            $queryTotal = $queryGroup->total;
            if ($this->isFloatZero($queryTotal)) {
                continue;
            }

            $queryId = $queryGroup->id;
            $code = $this->findGroupCode($queryId);
            if (!StringUtils::isString($code)) {
                continue;
            }

            $group = $this->getRowGroup($groups, $queryId, $code);
            $amount = $group->amount + $queryTotal;
            $marginPercent = $this->getGroupMargin($queryId, $amount);
            $total = $this->round($marginPercent * $amount);
            $marginAmount = $total - $amount;

            $group->marginPercent = $marginPercent;
            $group->marginAmount = $marginAmount;
            $group->amount = $amount;
            $group->total = $total;
        }

        return $groups;
    }

    /**
     * @return Collection<int, CalculationResultGroup>
     */
    private function createEmptyGroups(): Collection
    {
        return new ArrayCollection([$this->createResultGroup(self::ROW_EMPTY)]);
    }

    private function createEmptyParameters(): CalculationAdjustResult
    {
        return new CalculationAdjustResult(
            overallBelow: false,
            overallMargin: 0.0,
            overallTotal: 0.0,
            userMargin: 0.0,
            minMargin: $this->getMinMargin(),
            groups: $this->createEmptyGroups()
        );
    }

    private function createResultGroup(
        int $id,
        ?string $description = null,
        float $marginPercent = 0.0,
        float $marginAmount = 0.0,
        float $amount = 0.0,
        float $total = 0.0
    ): CalculationResultGroup {
        return new CalculationResultGroup(
            id: $id,
            description: $description ?? $this->translateRow($id),
            marginPercent: $marginPercent,
            marginAmount: $marginAmount,
            amount: $amount,
            total: $total
        );
    }

    private function findGroupCode(int $id): ?string
    {
        return $this->groupRepository->findGroupCode($id);
    }

    private function getGlobalMargin(float $amount): float
    {
        return $this->isFloatZero($amount) ? 0.0 : $this->globalMarginRepository->getMargin($amount);
    }

    /**
     * @param Collection<int, CalculationResultGroup> $groups
     */
    private function getGroup(Collection $groups, int $index): CalculationResultGroup
    {
        return $groups->get($index) ?? throw new \InvalidArgumentException(\sprintf('Unknown group: "%d".', $index));
    }

    private function getGroupMargin(int $id, float $amount): float
    {
        return $this->groupMarginRepository->getGroupMargin($id, $amount);
    }

    /**
     * @param Collection<int, CalculationResultGroup> $groups
     */
    private function getGroupsAmount(Collection $groups): float
    {
        return $groups->reduce(
            static fn (float $carry, CalculationResultGroup $group): float => $carry + $group->amount,
            0.0
        );
    }

    /**
     * @param Collection<int, CalculationResultGroup> $groups
     */
    private function getGroupsMargin(Collection $groups): float
    {
        return $groups->reduce(
            static fn (float $carry, CalculationResultGroup $group): float => $carry + $group->marginAmount,
            0.0
        );
    }

    private function getMinMargin(): float
    {
        return $this->parameters->getMinMargin();
    }

    /**
     * @param Collection<int, CalculationResultGroup> $groups
     */
    private function getRowGroup(Collection $groups, int $id, string $code): CalculationResultGroup
    {
        if (!$groups->containsKey($id)) {
            $groups->set($id, $this->createResultGroup(self::ROW_GROUP, $code));
        }

        return $this->getGroup($groups, $id);
    }

    private function translateRow(int $id): string
    {
        return match ($id) {
            self::ROW_EMPTY => $this->translator->trans('calculation.edit.empty'),
            self::ROW_TOTAL_GROUP => $this->translator->trans('calculation.fields.marginTotal'),
            self::ROW_GLOBAL_MARGIN => $this->translator->trans('calculation.fields.globalMargin'),
            self::ROW_TOTAL_NET => $this->translator->trans('calculation.fields.totalNet'),
            self::ROW_USER_MARGIN => $this->translator->trans('calculation.fields.userMargin'),
            self::ROW_OVERALL_TOTAL => $this->translator->trans('calculation.fields.overallTotal'),
            default => $this->translator->trans('common.value_unknown'),
        };
    }
}
