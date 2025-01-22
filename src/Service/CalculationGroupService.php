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
use App\Model\CalculationQuery;
use App\Repository\GlobalMarginRepository;
use App\Repository\GroupMarginRepository;
use App\Repository\GroupRepository;
use App\Traits\MathTrait;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to create total's groups used to render the total view.
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
 *
 * @psalm-import-type QueryGroupType from CalculationQuery
 */
class CalculationGroupService implements ConstantsInterface
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
     * @throws ORMException
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
                entityOrId: $group,
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
     * @throws ORMException
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
            $user_margin = $groups[self::ROW_USER_MARGIN]['margin_percent'];
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
     * Adjust the user margin to have the desired overall minimum margin.
     *
     * @psalm-param GroupType[] $groups
     *
     * @psalm-return GroupType[]
     */
    private function adjustUserMargin(array $groups): array
    {
        $total_net = $groups[self::ROW_TOTAL_NET]['total'];
        $total_group = $groups[self::ROW_TOTAL_GROUP]['amount'];

        $user_margin = $this->ceil((($total_group * $this->getMinMargin()) - $total_net) / $total_net);
        $user_total = $this->round($total_net * $user_margin);
        $user_margin_group = &$groups[self::ROW_USER_MARGIN];
        $user_margin_group['margin_percent'] = $user_margin;
        $user_margin_group['total'] = $user_total;

        $overall_total = $total_net + $user_total;
        $overall_total_group = &$groups[self::ROW_OVERALL_TOTAL];
        $overall_total_group['margin_percent'] = $this->floor($overall_total / $total_group);
        $overall_total_group['margin_amount'] = $overall_total - $total_group;
        $overall_total_group['total'] = $overall_total;

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
     * @throws ORMException
     */
    private function computeGroups(
        array $groups,
        float $user_margin,
        ?float $global_margin = null
    ): array {
        $groups_amount = $this->getGroupsAmount($groups);
        $groups_margin = $this->getGroupsMargin($groups);
        $total_net = $groups_amount + $groups_margin;
        $groups[self::ROW_TOTAL_GROUP] = $this->createGroup(
            id: self::ROW_TOTAL_GROUP,
            entityOrId: 'calculation.fields.marginTotal',
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
            entityOrId: 'calculation.fields.globalMargin',
            margin_percent: $global_margin,
            total: $global_amount
        );
        $groups[self::ROW_TOTAL_NET] = $this->createGroup(
            id: self::ROW_TOTAL_NET,
            entityOrId: 'calculation.fields.totalNet',
            total: $total_net
        );

        $user_amount = $this->round($total_net * $user_margin);
        $groups[self::ROW_USER_MARGIN] = $this->createGroup(
            id: self::ROW_USER_MARGIN,
            entityOrId: 'calculation.fields.userMargin',
            margin_percent: $user_margin,
            total: $user_amount
        );

        $overall_total = $total_net + $user_amount;
        $overall_amount = $overall_total - $groups_amount;
        $overall_margin = $this->safeDivide($overall_amount, $groups_amount);
        $overall_margin = $this->floor(1.0 + $overall_margin);
        $groups[self::ROW_OVERALL_TOTAL] = $this->createGroup(
            id: self::ROW_OVERALL_TOTAL,
            entityOrId: 'calculation.fields.overallTotal',
            amount: $groups_amount,
            margin_percent: $overall_margin,
            margin_amount: $overall_amount,
            total: $overall_total
        );

        return $groups;
    }

    /**
     * @param QueryGroupType[] $queryGroups
     *
     * @return GroupType[]
     *
     * @throws ORMException
     */
    private function convertQueryGroups(array $queryGroups): array
    {
        $groups = [];
        foreach ($queryGroups as $queryGroup) {
            if ($this->isFloatZero($queryGroup['total'])) {
                continue;
            }

            $id = $queryGroup['id'];
            $group = $this->findGroup($id);
            if (!$group instanceof Group) {
                continue;
            }

            if (!\array_key_exists($id, $groups)) {
                $groups[$id] = $this->createGroup(self::ROW_GROUP, $group);
            }

            $current = &$groups[$id];
            $amount = $current['amount'] + $queryGroup['total'];
            $margin_percent = $this->getGroupMargin($group, $amount);
            $total = $this->round($margin_percent * $amount);
            $margin_amount = $total - $amount;

            $current = \array_merge($current, [
                'margin_percent' => $margin_percent,
                'margin_amount' => $margin_amount,
                'amount' => $amount,
                'total' => $total,
            ]);
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
        EntityInterface|string $entityOrId,
        float $amount = 0.0,
        float $margin_percent = 0.0,
        float $margin_amount = 0.0,
        float $total = 0.0
    ): array {
        if ($entityOrId instanceof EntityInterface) {
            $description = $entityOrId->getDisplay();
        } else {
            $description = $this->translator->trans($entityOrId);
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
     * @throws ORMException
     */
    private function getGlobalMargin(float $amount): float
    {
        return $this->isFloatZero($amount) ? 0.0 : $this->globalMarginRepository->getMargin($amount);
    }

    /**
     * Gets the margin, in percent, for the given group and amount.
     *
     * @throws ORMException
     */
    private function getGroupMargin(Group $group, float $amount): float
    {
        return $this->groupMarginRepository->getMargin($group, $amount);
    }

    /**
     * Gets the sum of the group's amount.
     *
     * @psalm-param GroupType[] $groups
     */
    private function getGroupsAmount(array $groups): float
    {
        return $this->round(\array_sum(\array_column($groups, 'amount')));
    }

    /**
     * Gets the sum of the group's margin amount.
     *
     * @psalm-param GroupType[] $groups
     */
    private function getGroupsMargin(array $groups): float
    {
        return $this->round(\array_sum(\array_column($groups, 'margin_amount')));
    }

    /**
     * Gets the minimum margin, in percent, for a calculation.
     */
    private function getMinMargin(): float
    {
        return $this->service->getMinMargin();
    }
}
