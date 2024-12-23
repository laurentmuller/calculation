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
use App\Repository\GlobalMarginRepository;
use App\Repository\GroupMarginRepository;
use App\Repository\GroupRepository;
use App\Traits\MathTrait;
use App\Traits\TranslatorAwareTrait;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Service to update calculation totals.
 *
 * @psalm-type ServiceGroupType = array{
 *     id: int,
 *     description: string,
 *     amount: float,
 *     margin: float,
 *     margin_amount: float,
 *     total: float,
 *     overall_below?: bool}
 * @psalm-type ServiceParametersType = array{
 *     result: bool,
 *     overall_below: bool,
 *     overall_margin: float,
 *     overall_total: float,
 *     min_margin: float,
 *     user_margin: float,
 *     groups: ServiceGroupType[]}
 */
class CalculationService implements ServiceSubscriberInterface
{
    use MathTrait;
    use ServiceMethodsSubscriberTrait;
    use TranslatorAwareTrait;

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
        private readonly GlobalMarginRepository $globalRepository,
        private readonly GroupMarginRepository $marginRepository,
        private readonly GroupRepository $groupRepository,
        private readonly ApplicationService $service
    ) {
    }

    /**
     * Adjust the user margin to have the desired overall minimum margin.
     *
     * @psalm-param ServiceParametersType $parameters
     *
     * @psalm-suppress UnsupportedReferenceUsage
     */
    public function adjustUserMargin(array $parameters): array
    {
        $parameters['overall_below'] = false;
        $groups = &$parameters['groups'];
        $total_group = &$this->findOrCreateGroup($groups, self::ROW_TOTAL_GROUP);
        $net_group = &$this->findOrCreateGroup($groups, self::ROW_TOTAL_NET);
        $user_group = &$this->findOrCreateGroup($groups, self::ROW_USER_MARGIN);
        $overall_group = &$this->findOrCreateGroup($groups, self::ROW_OVERALL_TOTAL);
        $min_margin = $parameters['min_margin'];
        $total_amount = $total_group['amount'];
        $net_total = $net_group['total'];
        if ($this->isFloatZero($net_total) || $this->isFloatZero($total_amount)) {
            return $parameters;
        }
        $user_margin = (($total_amount * $min_margin) - $net_total) / $net_total;
        $user_margin = $this->ceil($user_margin);
        $user_group['margin'] = $user_margin;
        $user_group['total'] = $net_total * $user_margin;
        $overall_group['total'] = $net_total + $user_group['total'];
        $overall_group['margin'] = $this->floor($overall_group['total'] / $total_amount);
        $overall_group['margin_amount'] = $overall_group['total'] - $total_amount;
        $parameters['user_margin'] = (int) \floor(100.0 * $user_margin);

        return $parameters;
    }

    /**
     * Gets the row constants.
     *
     * @return array<string, mixed>
     */
    public static function constants(): array
    {
        $reflection = new \ReflectionClass(self::class);
        $constants = $reflection->getReflectionConstants(\ReflectionClassConstant::IS_PUBLIC);

        return \array_reduce(
            $constants,
            /** @psalm-param array<string, mixed> $carry */
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
     * @psalm-return non-empty-array<ServiceGroupType>
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
     * Creates groups from an array.
     *
     * @param array $source the form data as an array
     *
     * @return array an array with the computed values used to render the total view
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     *
     * @psalm-return ServiceParametersType
     */
    public function createGroupsFromData(array $source): array
    {
        /** @psalm-var ServiceGroupType[] $groups */
        $groups = [];
        $source_groups = $this->getArrayByKey($source, 'groups');
        if (!\is_array($source_groups)) {
            return $this->createEmptyParameters();
        }
        /** @psalm-var array<int, float> $item_groups */
        $item_groups = \array_reduce($source_groups, function (array $carry, array $group): array {
            $id = (int) $group['group'];
            $carry[$id] = $this->reduceGroup($group);

            return $carry;
        }, []);
        foreach ($item_groups as $key => $value) {
            if ($this->isFloatZero($value)) {
                continue;
            }
            $group = $this->findGroup($key);
            if (!$group instanceof Group) {
                continue;
            }
            $id = (int) $group->getId();
            if (!\array_key_exists($id, $groups)) {
                $groups[$id] = $this->createGroup(self::ROW_GROUP, (string) $group->getCode());
            }
            $current = &$groups[$id];
            $amount = $current['amount'] + $value;
            $margin = $this->getGroupMargin($group, $amount);
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
        $user_margin = (float) $source['userMargin'] / 100.0;
        $groups = $this->computeGroups(groups: $groups, user_margin: $user_margin);
        $last_group = \end($groups);
        $overall_total = $last_group['total'];
        $overall_margin = $last_group['margin'];
        $overall_below = !$this->isFloatZero($overall_total) && $this->service->isMarginBelow($overall_margin);

        return [
            'result' => true,
            'overall_margin' => $overall_margin,
            'overall_total' => $overall_total,
            'overall_below' => $overall_below,
            'user_margin' => $user_margin,
            'min_margin' => 0.0,
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
     * Finds or create a group for the given identifier.
     *
     * @psalm-param ServiceGroupType[] $groups
     *
     * @psalm-return ServiceGroupType
     */
    private function &findOrCreateGroup(array &$groups, int $id): array
    {
        foreach ($groups as &$group) {
            if ($group['id'] === $id) {
                return $group;
            }
        }
        $groups[] = $new_group = $this->createGroup($id, $this->trans('common.value_unknown'));

        return $new_group;
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
     * @psalm-return non-empty-array<ServiceGroupType>
     */
    private function computeGroups(
        array $groups,
        float $user_margin,
        ?callable $callback = null,
        ?float $global_margin = null
    ): array {
        /** @psalm-var ServiceGroupType[] $result */
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
     * @psalm-return ServiceGroupType
     */
    private function createEmptyGroup(): array
    {
        return $this->createGroup(self::ROW_EMPTY, $this->trans('calculation.edit.empty'));
    }

    /**
     * @psalm-return ServiceParametersType
     */
    private function createEmptyParameters(bool $result = true): array
    {
        return [
            'result' => $result,
            'overall_below' => false,
            'overall_margin' => 0.0,
            'overall_total' => 0.0,
            'min_margin' => 0.0,
            'user_margin' => 0.0,
            'groups' => [$this->createEmptyGroup()],
        ];
    }

    /**
     * @psalm-return ServiceGroupType
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
    private function findGroup(int $id): ?Group
    {
        return $this->groupRepository->find($id);
    }

    private function floor(float $value): float
    {
        return \floor($value * 100.0) / 100.0;
    }

    private function getArrayByKey(array $array, string $key): ?array
    {
        return (\array_key_exists($key, $array) && \is_array($array[$key]) && [] !== $array[$key]) ? $array[$key] : null;
    }

    /**
     * Gets the global margin, in percent, for the given amount.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function getGlobalMargin(float $amount): float
    {
        if (0.0 !== $amount) {
            return $this->globalRepository->getMargin($amount);
        }

        return 0;
    }

    /**
     * Gets the margin, in percent, for the given group and amount.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function getGroupMargin(Group $group, float $amount): float
    {
        if (0.0 !== $amount) {
            return $this->marginRepository->getMargin($group, $amount);
        }

        return 0;
    }

    /**
     * Gets groups total amount.
     */
    private function getGroupsAmount(array $groups): float
    {
        return \array_reduce(
            $groups,
            /** @psalm-param ServiceGroupType $group */
            static fn (float $carry, array $group): float => $carry + $group['amount'],
            0.0
        );
    }

    /**
     * Gets groups total margin amount.
     */
    private function getGroupsMargin(array $groups): float
    {
        return \array_reduce(
            $groups,
            /** @psalm-param ServiceGroupType $group */
            static fn (float $carry, array $group): float => $carry + $group['margin_amount'],
            0.0
        );
    }

    private function reduceCategory(array $category): float
    {
        $items = $this->getArrayByKey($category, 'items');
        if (!\is_array($items)) {
            return 0.0;
        }

        return \array_reduce(
            $items,
            /** @psalm-param array{price: float, quantity: float} $item */
            static fn (float $carry, array $item): float => $carry + ($item['price'] * $item['quantity']),
            0.0
        );
    }

    private function reduceGroup(array $group): float
    {
        $categories = $this->getArrayByKey($group, 'categories');
        if (!\is_array($categories)) {
            return 0.0;
        }

        return \array_reduce(
            $categories,
            fn (float $carry, array $category): float => $carry + $this->reduceCategory($category),
            0.0
        );
    }
}
