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
use App\Interfaces\EntityInterface;
use App\Model\CalculationAdjustQuery;
use App\Model\GroupType;
use App\Model\QueryGroupType;
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
 *
 * @phpstan-type ParametersType = array{
 *     result: bool,
 *     overall_below: bool,
 *     overall_margin: float,
 *     overall_total: float,
 *     min_margin: float,
 *     user_margin: float,
 *     groups: Collection<int, GroupType>}
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
     * Creates the total groups, used to render the total view, from the given calculation.
     *
     * @return Collection<int, GroupType>
     */
    public function createGroups(Calculation $calculation): Collection
    {
        if ($calculation->isEmpty()) {
            return $this->createEmptyGroups();
        }

        $groups = $calculation->getGroups()
            ->map(fn (CalculationGroup $group): GroupType => $this->createGroupType(
                id: self::ROW_GROUP,
                entityOrId: $group,
                marginPercent: $group->getMargin(),
                marginAmount: $group->getMarginAmount(),
                amount: $group->getAmount(),
                total: $group->getTotal(),
            ));

        return $this->createTotalGroups($groups, $calculation->getUserMargin(), $calculation->getGlobalMargin());
    }

    /**
     * Creates parameters, used to render the total view, for the given query.
     *
     * @phpstan-return ParametersType
     */
    public function createParameters(CalculationAdjustQuery $query): array
    {
        if ([] === $query->groups) {
            return $this->createEmptyParameters();
        }

        $groups = $this->convertQueryGroups($query->groups);
        if ($groups->isEmpty()) {
            return $this->createEmptyParameters();
        }

        $userMargin = $query->userMargin;
        $groups = $this->createTotalGroups($groups, $userMargin);
        $overallGroup = $this->getGroupType($groups, self::ROW_OVERALL_TOTAL);
        $overallTotal = $overallGroup->total;
        $overallMargin = $overallGroup->marginPercent;
        $overallBelow = $this->parameters->isMarginBelow($overallMargin);

        if ($query->adjust && $overallBelow) {
            $groups = $this->adjustUserMargin($groups);
            $userMargin = $this->getGroupType($groups, self::ROW_USER_MARGIN)->marginPercent;
            $overallBelow = false;
        }

        return [
            'result' => true,
            'overall_margin' => $overallMargin,
            'overall_total' => $overallTotal,
            'overall_below' => $overallBelow,
            'user_margin' => $userMargin,
            'min_margin' => $this->getMinMargin(),
            'groups' => $groups,
        ];
    }

    /**
     * Adjust the user margin to have the desired overall minimum margin.
     *
     * @param Collection<int, GroupType> $groups
     *
     * @return Collection<int, GroupType>
     */
    private function adjustUserMargin(Collection $groups): Collection
    {
        $totalNet = $this->getGroupType($groups, self::ROW_TOTAL_NET)->total;
        $totalGroup = $this->getGroupType($groups, self::ROW_TOTAL_GROUP)->amount;

        $userMargin = $this->ceil((($totalGroup * $this->getMinMargin()) - $totalNet) / $totalNet);
        $userTotal = $this->round($totalNet * $userMargin);
        $userMarginGroup = $this->getGroupType($groups, self::ROW_USER_MARGIN);
        $userMarginGroup->marginPercent = $userMargin;
        $userMarginGroup->total = $userTotal;

        $overallTotal = $totalNet + $userTotal;
        $overallTotalGroup = $this->getGroupType($groups, self::ROW_OVERALL_TOTAL);
        $overallTotalGroup->marginPercent = $this->floor($overallTotal / $totalGroup);
        $overallTotalGroup->marginAmount = $overallTotal - $totalGroup;
        $overallTotalGroup->total = $overallTotal;

        return $groups;
    }

    /**
     * @param QueryGroupType[] $queryGroups
     *
     * @return Collection<int, GroupType>
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

            if (!$groups->containsKey($queryId)) {
                $groups->set($queryId, $this->createGroupType(self::ROW_GROUP, $code));
            }

            $group = $this->getGroupType($groups, $queryId);
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
     * Creates the group when no data is present.
     *
     * @return Collection<int, GroupType>
     */
    private function createEmptyGroups(): Collection
    {
        return new ArrayCollection(
            [$this->createGroupType(self::ROW_EMPTY, 'calculation.edit.empty')]
        );
    }

    /**
     * @phpstan-return ParametersType
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
            'groups' => $this->createEmptyGroups(),
        ];
    }

    private function createGroupType(
        int $id,
        EntityInterface|string $entityOrId,
        float $marginPercent = 0.0,
        float $marginAmount = 0.0,
        float $amount = 0.0,
        float $total = 0.0
    ): GroupType {
        return new GroupType(
            id: $id,
            description: $this->getGroupDescription($entityOrId),
            marginPercent: $marginPercent,
            marginAmount: $marginAmount,
            amount: $amount,
            total: $total,
        );
    }

    /**
     * Creates calculation's total groups.
     *
     * @param Collection<int, GroupType> $groups       the group types
     * @param float                      $userMargin   the user margin in percent
     * @param ?float                     $globalMargin the global margin, in percent, or null to compute
     *                                                 the new global margin
     *
     * @return Collection<int, GroupType> the computed groups
     */
    private function createTotalGroups(
        Collection $groups,
        float $userMargin,
        ?float $globalMargin = null
    ): Collection {
        $groupTypesAmount = $this->getGroupTypesAmount($groups);
        $groupTypesMargin = $this->getGroupTypesMargin($groups);
        $totalNet = $groupTypesAmount + $groupTypesMargin;
        $groups->set(self::ROW_TOTAL_GROUP, $this->createGroupType(
            id: self::ROW_TOTAL_GROUP,
            entityOrId: 'calculation.fields.marginTotal',
            marginPercent: 1.0 + $this->round($this->safeDivide($groupTypesMargin, $groupTypesAmount)),
            marginAmount: $groupTypesMargin,
            amount: $groupTypesAmount,
            total: $totalNet
        ));

        $globalMargin ??= $this->getGlobalMargin($totalNet);
        $globalAmount = $this->round($totalNet * ($globalMargin - 1.0));
        $groups->set(self::ROW_GLOBAL_MARGIN, $this->createGroupType(
            id: self::ROW_GLOBAL_MARGIN,
            entityOrId: 'calculation.fields.globalMargin',
            marginPercent: $globalMargin,
            amount: $globalMargin,
            total: $globalAmount
        ));

        $totalNet += $globalAmount;
        $groups->set(self::ROW_TOTAL_NET, $this->createGroupType(
            id: self::ROW_TOTAL_NET,
            entityOrId: 'calculation.fields.totalNet',
            amount: $totalNet,
            total: $totalNet
        ));

        $userAmount = $this->round($totalNet * $userMargin);
        $groups->set(self::ROW_USER_MARGIN, $this->createGroupType(
            id: self::ROW_USER_MARGIN,
            entityOrId: 'calculation.fields.userMargin',
            marginPercent: $userMargin,
            amount: $userMargin,
            total: $userAmount
        ));

        $overallTotal = $totalNet + $userAmount;
        $overallAmount = $overallTotal - $groupTypesAmount;
        $overallMargin = $this->safeDivide($overallAmount, $groupTypesAmount);
        $overallMargin = $this->floor(1.0 + $overallMargin);
        $groups->set(self::ROW_OVERALL_TOTAL, $this->createGroupType(
            id: self::ROW_OVERALL_TOTAL,
            entityOrId: 'calculation.fields.overallTotal',
            marginPercent: $overallMargin,
            marginAmount: $overallAmount,
            amount: $groupTypesAmount,
            total: $overallTotal
        ));

        return $groups;
    }

    /**
     * Find a group for the given identifier.
     */
    private function findGroupCode(int $id): ?string
    {
        return $this->groupRepository->findGroupCode($id);
    }

    /**
     * Gets the global margin, in percent, for the given amount.
     */
    private function getGlobalMargin(float $amount): float
    {
        return $this->isFloatZero($amount) ? 0.0 : $this->globalMarginRepository->getMargin($amount);
    }

    /**
     * Gets the group's description.
     */
    private function getGroupDescription(EntityInterface|string $entityOrId): string
    {
        return $entityOrId instanceof EntityInterface
            ? $entityOrId->getDisplay()
            : $this->translator->trans($entityOrId);
    }

    /**
     * Gets the margin, in percent, for the given group identifier and amount.
     */
    private function getGroupMargin(int $id, float $amount): float
    {
        return $this->groupMarginRepository->getGroupMargin($id, $amount);
    }

    /**
     * @param Collection<int, GroupType> $groups
     */
    private function getGroupType(Collection $groups, int $index): GroupType
    {
        /** @phpstan-var GroupType */
        return $groups->get($index);
    }

    /**
     * @param Collection<int, GroupType> $groups
     */
    private function getGroupTypesAmount(Collection $groups): float
    {
        return $groups->reduce(static fn (float $carry, GroupType $group): float => $carry + $group->amount, 0.0);
    }

    /**
     * @param Collection<int, GroupType> $groups
     */
    private function getGroupTypesMargin(Collection $groups): float
    {
        return $groups->reduce(static fn (float $carry, GroupType $group): float => $carry + $group->marginAmount, 0.0);
    }

    /**
     * Gets the minimum margin, in percent, for a calculation.
     */
    private function getMinMargin(): float
    {
        return $this->parameters->getMinMargin();
    }
}
