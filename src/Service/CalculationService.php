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
use App\Entity\Group;
use App\Interfaces\ConstantsInterface;
use App\Interfaces\EntityInterface;
use App\Model\CalculationGroupQuery;
use App\Model\CalculationQuery;
use App\Repository\GlobalMarginRepository;
use App\Repository\GroupMarginRepository;
use App\Repository\GroupRepository;
use App\Traits\MathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to update calculation totals.
 *
 * @implements ConstantsInterface<int>
 *
 * @psalm-type GroupType = array{
 *     id: int,
 *     description: string,
 *     amount: float,
 *     margin_percent: float,
 *     margin_amount: float,
 *     total: float}
 * @psalm-type ParametersType = array{
 *     result: bool,
 *     overall_below: bool,
 *     overall_margin: float,
 *     overall_total: float,
 *     min_margin: float,
 *     user_margin: float,
 *     groups: GroupType[]}
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
        private readonly ApplicationService $service,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Gets the row constants.
     *
     * @return array<string, int>
     */
    public static function constants(): array
    {
        $reflection = new \ReflectionClass(self::class);
        $constants = $reflection->getReflectionConstants(\ReflectionClassConstant::IS_PRIVATE);

        /** @psalm-var array<string, int> */
        return \array_reduce(
            $constants,
            static fn (array $carry, \ReflectionClassConstant $c): array => $carry + [$c->getName() => $c->getValue()],
            []
        );
    }

    /**
     * Creates groups, used to render the total view, from the given calculation.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     *
     * @psalm-return non-empty-array<GroupType>
     */
    public function createGroups(Calculation $calculation): array
    {
        if ($calculation->isEmpty()) {
            return [$this->createEmptyGroup()];
        }

        $groups = \array_map(
            fn (CalculationGroup $group): array => $this->createGroup(
                id: self::ROW_GROUP,
                description: $group,
                amount: $group->getAmount(),
                margin_percent: $group->getMargin(),
                margin_amount: $group->getMarginAmount(),
                total: $group->getTotal(),
            ),
            $calculation->getGroups()->toArray()
        );

        return $this->computeGroups(
            $groups,
            $calculation->getUserMargin(),
            $calculation->getGlobalMargin()
        );
    }

    /**
     * Creates parameters, used to render the total view, for the given query.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     *
     * @psalm-return ParametersType
     */
    public function createParameters(CalculationQuery $query): array
    {
        if ([] === $query->groups) {
            return $this->createEmptyParameters();
        }

        $groups = $this->convertQueryGroups($query->groups);
        if ([] === $groups) {
            return $this->createEmptyParameters();
        }

        $user_margin = $query->userMargin;
        $groups = $this->computeGroups($groups, $user_margin);
        $overall_group = $groups[self::ROW_OVERALL_TOTAL];
        $overall_total = $overall_group['total'];
        $overall_margin = $overall_group['margin_percent'];
        $overall_below = !$this->isFloatZero($overall_total) && $this->service->isMarginBelow($overall_margin);

        if ($query->adjust && $overall_below) {
            $groups = $this->adjustUserMargin($groups);
            $user_group = $groups[self::ROW_USER_MARGIN];
            $user_margin = $user_group['margin_percent'];
            $overall_below = false;
        }

        return [
            'result' => true,
            'overall_margin' => $overall_margin,
            'overall_total' => $overall_total,
            'overall_below' => $overall_below,
            'user_margin' => $user_margin,
            'min_margin' => $this->getMinMargin(),
            'groups' => $groups,
        ];
    }

    /**
     * Update the calculation's total.
     *
     * @return bool true if updated; false otherwise
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function updateTotal(Calculation $calculation): bool
    {
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

        // 3. update
        $calculation->setItemsTotal($items_total)
            ->setGlobalMargin($global_margin)
            ->setOverallTotal($overall_total);

        return true;
    }

    /**
     * Adjust the user margin to have the desired overall minimum margin.
     *
     * @psalm-param GroupType[] $groups
     *
     * @psalm-return GroupType[]
     */
    private function adjustUserMargin(array $groups): array
    {
        $total_group = &$groups[self::ROW_TOTAL_GROUP];
        $total_net_group = &$groups[self::ROW_TOTAL_NET];
        $user_margin_group = &$groups[self::ROW_USER_MARGIN];
        $overall_total_group = &$groups[self::ROW_OVERALL_TOTAL];

        $total_amount = $total_group['amount'];
        $net_total = $total_net_group['total'];
        $user_margin = (($total_amount * $this->getMinMargin()) - $net_total) / $net_total;
        $user_margin = $this->ceil($user_margin);
        $user_margin_group['margin_percent'] = $user_margin;
        $user_margin_group['total'] = $net_total * $user_margin;

        $overall_total_group['total'] = $net_total + $user_margin_group['total'];
        $overall_total_group['margin_percent'] = $this->floor($overall_total_group['total'] / $total_amount);
        $overall_total_group['margin_amount'] = $overall_total_group['total'] - $total_amount;

        return $groups;
    }

    /**
     * Creates calculation's total groups.
     *
     * @psalm-param GroupType[] $groups   the group types
     * @psalm-param float $user_margin    the user margin
     * @psalm-param ?float $global_margin the global margin or null to compute the new global margin
     *
     * @psalm-return non-empty-array<GroupType>
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function computeGroups(
        array $groups,
        float $user_margin,
        ?float $global_margin = null
    ): array {
        $groups_amount = $this->round($this->getGroupsAmount($groups));
        $groups_margin = $this->round($this->getGroupsMargin($groups));
        $total_net = $groups_amount + $groups_margin;
        $groups[self::ROW_TOTAL_GROUP] = $this->createGroup(
            id: self::ROW_TOTAL_GROUP,
            description: 'calculation.fields.marginTotal',
            amount: $groups_amount,
            margin_percent: 1.0 + $this->round($this->safeDivide($groups_margin, $groups_amount)),
            margin_amount: $groups_margin,
            total: $total_net
        );

        $global_margin ??= $this->getGlobalMargin($total_net);
        $global_amount = $this->round($total_net * ($global_margin - 1.0));
        $total_net += $global_amount;
        $groups[self::ROW_GLOBAL_MARGIN] = $this->createGroup(
            id: self::ROW_GLOBAL_MARGIN,
            description: 'calculation.fields.globalMargin',
            margin_percent: $global_margin,
            total: $global_amount
        );
        $groups[self::ROW_TOTAL_NET] = $this->createGroup(
            id: self::ROW_TOTAL_NET,
            description: 'calculation.fields.totalNet',
            total: $total_net
        );

        $user_amount = $this->round($total_net * $user_margin);
        $groups[self::ROW_USER_MARGIN] = $this->createGroup(
            id: self::ROW_USER_MARGIN,
            description: 'calculation.fields.userMargin',
            margin_percent: $user_margin,
            total: $user_amount
        );

        $overall_total = $total_net + $user_amount;
        $overall_amount = $overall_total - $groups_amount;
        $overall_margin = $this->safeDivide($overall_amount, $groups_amount);
        $overall_margin = $this->floor(1.0 + $overall_margin);
        $groups[self::ROW_OVERALL_TOTAL] = $this->createGroup(
            id: self::ROW_OVERALL_TOTAL,
            description: 'calculation.fields.overallTotal',
            amount: $groups_amount,
            margin_percent: $overall_margin,
            margin_amount: $overall_amount,
            total: $overall_total
        );

        return $groups;
    }

    /**
     * @param CalculationGroupQuery[] $queryGroups
     *
     * @return GroupType[]
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function convertQueryGroups(array $queryGroups): array
    {
        $groups = [];
        foreach ($queryGroups as $group) {
            if ($this->isFloatZero($group->total)) {
                continue;
            }

            $id = $group->id;
            $targetGroup = $this->findGroup($id);
            if (!$targetGroup instanceof Group) {
                continue;
            }

            if (!\array_key_exists($id, $groups)) {
                $groups[$id] = $this->createGroup(self::ROW_GROUP, $targetGroup);
            }

            $current = &$groups[$id];
            $amount = $current['amount'] + $group->total;
            $margin_percent = $this->getGroupMargin($targetGroup, $amount);
            $total = $this->round($margin_percent * $amount);
            $current['amount'] = $amount;
            $current['margin_percent'] = $margin_percent;
            $current['margin_amount'] = $total - $amount;
            $current['total'] = $total;
        }

        return $groups;
    }

    /**
     * Creates the group when no data is present.
     *
     * @psalm-return GroupType
     */
    private function createEmptyGroup(): array
    {
        return $this->createGroup(self::ROW_EMPTY, 'calculation.edit.empty');
    }

    /**
     * @psalm-return ParametersType
     */
    private function createEmptyParameters(): array
    {
        return [
            'result' => true,
            'overall_below' => false,
            'overall_margin' => 0.0,
            'overall_total' => 0.0,
            'user_margin' => 0.0,
            'min_margin' => $this->getMinMargin(),
            'groups' => [$this->createEmptyGroup()],
        ];
    }

    /**
     * @psalm-return GroupType
     */
    private function createGroup(
        int $id,
        EntityInterface|string $description,
        float $amount = 0.0,
        float $margin_percent = 0.0,
        float $margin_amount = 0.0,
        float $total = 0.0
    ): array {
        if ($description instanceof EntityInterface) {
            $description = (string) $description;
        } else {
            $description = $this->translator->trans($description);
        }

        return [
            'id' => $id,
            'description' => $description,
            'amount' => $amount,
            'margin_percent' => $margin_percent,
            'margin_amount' => $margin_amount,
            'total' => $total,
        ];
    }

    /**
     * Find a group for the given identifier.
     */
    private function findGroup(int $id): ?Group
    {
        return $this->groupRepository->find($id);
    }

    /**
     * Gets the global margin, in percent, for the given amount.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function getGlobalMargin(float $amount): float
    {
        if (!$this->isFloatZero($amount)) {
            return $this->globalMarginRepository->getMargin($amount);
        }

        return $amount;
    }

    /**
     * Gets the margin, in percent, for the given group and amount.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function getGroupMargin(Group $group, float $amount): float
    {
        return $this->groupMarginRepository->getMargin($group, $amount);
    }

    /**
     * Gets groups total amount.
     *
     * @psalm-param GroupType[] $groups
     */
    private function getGroupsAmount(array $groups): float
    {
        return \array_reduce(
            $groups,
            /** @psalm-param GroupType $group */
            static fn (float $carry, array $group): float => $carry + $group['amount'],
            0.0
        );
    }

    /**
     * Gets groups total margin amount.
     *
     * @psalm-param GroupType[] $groups
     */
    private function getGroupsMargin(array $groups): float
    {
        return \array_reduce(
            $groups,
            /** @psalm-param GroupType $group */
            static fn (float $carry, array $group): float => $carry + $group['margin_amount'],
            0.0
        );
    }

    /**
     * Gets the minimum margin, in percent, for a calculation.
     */
    private function getMinMargin(): float
    {
        return $this->service->getMinMargin();
    }
}
