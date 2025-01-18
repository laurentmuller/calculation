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
use App\Model\CalculationQuery;
use App\Repository\GlobalMarginRepository;
use App\Repository\GroupMarginRepository;
use App\Repository\GroupRepository;
use App\Traits\MathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to update calculation totals.
 *
 * @psalm-type GroupType = array{
 *     id: int,
 *     description: string,
 *     amount: float,
 *     margin: float,
 *     margin_amount: float,
 *     total: float,
 *     overall_below?: bool}
 * @psalm-type ParametersType = array{
 *     result: bool,
 *     overall_below: bool,
 *     overall_margin: float,
 *     overall_total: float,
 *     min_margin: float,
 *     user_margin: float,
 *     groups: GroupType[]}
 */
class CalculationService
{
    use MathTrait;

    /**
     * Empty row identifier.
     */
    public const ROW_EMPTY = 0;

    /**
     * Global margin row identifier.
     */
    public const ROW_GLOBAL_MARGIN = 3;

    /**
     * Group row identifier.
     */
    public const ROW_GROUP = 1;

    /**
     * Overall total row identifier.
     */
    public const ROW_OVERALL_TOTAL = 6;

    /**
     * Total group row identifier.
     */
    public const ROW_TOTAL_GROUP = 2;

    /**
     * Total net row identifier.
     */
    public const ROW_TOTAL_NET = 4;

    /**
     * User margin row identifier.
     */
    public const ROW_USER_MARGIN = 5;

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
        $constants = $reflection->getReflectionConstants(\ReflectionClassConstant::IS_PUBLIC);

        /** @psalm-var array<string, int> */
        return \array_reduce(
            $constants,
            static fn (array $carry, \ReflectionClassConstant $c): array => $carry + [$c->getName() => $c->getValue()],
            []
        );
    }

    /**
     * Creates groups from a calculation.
     *
     * @param Calculation $calculation the calculation to get groups from
     *
     * @return array an array with the computed values used to render the total view
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     *
     * @psalm-return non-empty-array<GroupType>
     */
    public function createGroupsFromCalculation(Calculation $calculation): array
    {
        if ($calculation->isEmpty()) {
            return [$this->createEmptyGroup()];
        }
        $mapper = fn (CalculationGroup $group): array => $this->createGroup(
            id: self::ROW_GROUP,
            description: (string) $group->getCode(),
            amount: $group->getAmount(),
            margin: $group->getMargin(),
            margin_amount: $group->getMarginAmount(),
            total: $group->getTotal(),
        );
        $user_margin = $calculation->getUserMargin();
        $global_margin = $calculation->getGlobalMargin();
        $groups = $calculation->getGroups()->toArray();

        return $this->computeGroups($groups, $user_margin, $mapper, $global_margin);
    }

    /**
     * Creates groups from a query.
     *
     * @return array an array with the computed values used to render the total view
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     *
     * @psalm-return ParametersType
     */
    public function createGroupsFromQuery(CalculationQuery $query): array
    {
        if ([] === $query->groups) {
            return $this->createEmptyParameters();
        }

        /** @psalm-var GroupType[] $groups */
        $groups = [];
        foreach ($query->groups as $group) {
            if ($this->isFloatZero($group->total)) {
                continue;
            }

            $id = $group->id;
            $targetGroup = $this->findGroupFromRepository($id);
            if (!$targetGroup instanceof Group) {
                continue;
            }

            if (!\array_key_exists($id, $groups)) {
                $groups[$id] = $this->createGroup(self::ROW_GROUP, (string) $targetGroup->getCode());
            }

            $current = &$groups[$id];
            $amount = $current['amount'] + $group->total;
            $margin = $this->getGroupMargin($targetGroup, $amount);
            $total = $this->round($margin * $amount);
            $margin_amount = $total - $amount;
            $current['amount'] = $amount;
            $current['margin'] = $margin;
            $current['margin_amount'] = $margin_amount;
            $current['total'] = $total;
        }

        if ([] === $groups) {
            return $this->createEmptyParameters();
        }

        $user_margin = $query->userMargin;
        $groups = $this->computeGroups(groups: $groups, user_margin: $user_margin);
        $last_group = \end($groups);
        $overall_total = $last_group['total'];
        $overall_margin = $last_group['margin'];
        $overall_below = !$this->isFloatZero($overall_total) && $this->service->isMarginBelow($overall_margin);

        if ($query->adjust && $overall_below) {
            $this->adjustUserMargin($groups);
            $user_group = $this->findGroupFromArray($groups, self::ROW_USER_MARGIN);
            $user_margin = $user_group['margin'];
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
     * Gets the minimum margin, in percent, for a calculation.
     */
    public function getMinMargin(): float
    {
        return $this->service->getMinMargin();
    }

    /**
     * Update the calculation's total.
     *
     * @param Calculation $calculation the calculation to update
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
     * Finds a group for the given identifier.
     *
     * @psalm-param GroupType[] $groups
     *
     * @psalm-return GroupType
     *
     * @throws \LogicException if the group is not found
     */
    private function &findGroupFromArray(array &$groups, int $id): array
    {
        foreach ($groups as &$group) {
            if ($group['id'] === $id) {
                return $group;
            }
        }
        throw new \LogicException(\sprintf('Group "%d" not found.', $id));
    }

    /**
     * Adjust the user margin to have the desired overall minimum margin.
     *
     * @psalm-param GroupType[] $groups
     *
     * @psalm-suppress UnsupportedReferenceUsage
     */
    private function adjustUserMargin(array &$groups): void
    {
        $total_group = &$this->findGroupFromArray($groups, self::ROW_TOTAL_GROUP);
        $net_group = &$this->findGroupFromArray($groups, self::ROW_TOTAL_NET);
        $user_group = &$this->findGroupFromArray($groups, self::ROW_USER_MARGIN);
        $overall_group = &$this->findGroupFromArray($groups, self::ROW_OVERALL_TOTAL);

        $min_margin = $this->service->getMinMargin();
        $total_amount = $total_group['amount'];
        $net_total = $net_group['total'];
        $user_margin = (($total_amount * $min_margin) - $net_total) / $net_total;
        $user_margin = $this->ceil($user_margin);

        $user_group['margin'] = $user_margin;
        $user_group['total'] = $net_total * $user_margin;

        $overall_group['total'] = $net_total + $user_group['total'];
        $overall_group['margin'] = $this->floor($overall_group['total'] / $total_amount);
        $overall_group['margin_amount'] = $overall_group['total'] - $total_amount;
    }

    private function ceil(float $value): float
    {
        return \ceil($value * 100.0) / 100.0;
    }

    /**
     * Creates calculation's total groups.
     *
     * @param array     $groups        the calculation groups
     * @param float     $user_margin   the user margin
     * @param ?callable $callback      the function to create group lines
     * @param ?float    $global_margin the global margin or null to compute the new global margin
     *
     * @return array the total groups
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     *
     * @psalm-return non-empty-array<GroupType>
     */
    private function computeGroups(
        array $groups,
        float $user_margin,
        ?callable $callback = null,
        ?float $global_margin = null
    ): array {
        /** @psalm-var GroupType[] $result */
        $result = \is_callable($callback) ? \array_map($callback, $groups) : $groups;
        $groups_amount = $this->round($this->getGroupsAmount($result));
        $groups_margin = $this->round($this->getGroupsMargin($result));
        $total_net = $groups_amount + $groups_margin;
        $result[] = $this->createGroup(
            id: self::ROW_TOTAL_GROUP,
            description: $this->trans('calculation.fields.marginTotal'),
            amount: $groups_amount,
            margin: 1.0 + $this->round($this->safeDivide($groups_margin, $groups_amount)),
            margin_amount: $groups_margin,
            total: $total_net
        );
        $global_margin ??= $this->getGlobalMargin($total_net);
        $global_amount = $this->round($total_net * ($global_margin - 1.0));
        $total_net += $global_amount;
        $result[] = $this->createGroup(
            id: self::ROW_GLOBAL_MARGIN,
            description: $this->trans('calculation.fields.globalMargin'),
            margin: $global_margin,
            total: $global_amount
        );
        $result[] = $this->createGroup(
            id: self::ROW_TOTAL_NET,
            description: $this->trans('calculation.fields.totalNet'),
            total: $total_net
        );
        $user_amount = $this->round($total_net * $user_margin);
        $result[] = $this->createGroup(
            id: self::ROW_USER_MARGIN,
            description: $this->trans('calculation.fields.userMargin'),
            margin: $user_margin,
            total: $user_amount
        );
        $overall_total = $total_net + $user_amount;
        $overall_amount = $overall_total - $groups_amount;
        $overall_margin = $this->safeDivide($overall_amount, $groups_amount);
        $overall_margin = $this->floor(1.0 + $overall_margin);
        $overall_below = [] !== $groups && !$this->isFloatZero($overall_total) && $this->service->isMarginBelow($overall_margin);
        $result[] = $this->createGroup(
            id: self::ROW_OVERALL_TOTAL,
            description: $this->trans('calculation.fields.overallTotal'),
            amount: $groups_amount,
            margin: $overall_margin,
            margin_amount: $overall_amount,
            total: $overall_total,
            overall_below: $overall_below
        );

        return $result;
    }

    /**
     * Creates the group when no data is present.
     *
     * @psalm-return GroupType
     */
    private function createEmptyGroup(): array
    {
        return $this->createGroup(self::ROW_EMPTY, $this->trans('calculation.edit.empty'));
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
        string $description,
        float $amount = 0.0,
        float $margin = 0.0,
        float $margin_amount = 0.0,
        float $total = 0.0,
        ?bool $overall_below = null
    ): array {
        $result = [
            'id' => $id,
            'description' => $description,
            'amount' => $amount,
            'margin' => $margin,
            'margin_amount' => $margin_amount,
            'total' => $total,
        ];
        if (null !== $overall_below) {
            $result['overall_below'] = $overall_below;
        }

        return $result;
    }

    /**
     * Find a group for the given identifier.
     */
    private function findGroupFromRepository(int $id): ?Group
    {
        return $this->groupRepository->find($id);
    }

    private function floor(float $value): float
    {
        return \floor($value * 100.0) / 100.0;
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

    private function trans(string $id): string
    {
        return $this->translator->trans($id);
    }
}
