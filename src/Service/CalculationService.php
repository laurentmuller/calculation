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
use App\Traits\TranslatorTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to update calculation totals.
 */
final class CalculationService
{
    use MathTrait;
    use TranslatorTrait;

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

    /**
     * Constructor.
     */
    public function __construct(
        private readonly GlobalMarginRepository $globalRepository,
        private readonly GroupMarginRepository $marginRepository,
        private readonly GroupRepository $groupRepository,
        private readonly ApplicationService $service,
        TranslatorInterface $translator
    ) {
        $this->translator = $translator;
    }

    /**
     * Adjust the user margin to have the desired overall minimum margin.
     *
     * @param array $parameters the parameters (rows) to update
     */
    public function adjustUserMargin(array &$parameters): void
    {
        // no more below
        $parameters['overall_below'] = false;

        // get rows
        /** @psalm-var array<array> $groups */
        $groups = &$parameters['groups'];
        $total_group = &$this->findGroup($groups, self::ROW_TOTAL_GROUP);
        $net_group = &$this->findGroup($groups, self::ROW_TOTAL_NET);
        $user_group = &$this->findGroup($groups, self::ROW_USER_MARGIN);
        $overall_group = &$this->findGroup($groups, self::ROW_OVERALL_TOTAL);

        // get values
        $min_margin = (float) $parameters['min_margin'];
        $total_amount = (float) $total_group['amount'];
        $net_total = (float) $net_group['total'];

        // net total?
        if ($this->isFloatZero($net_total)) {
            return;
        }

        // compute user margin to reach minimum and round up
        $user_margin = (($total_amount * $min_margin) - $net_total) / $net_total;
        $user_margin = \ceil($user_margin * 100.0) / 100.0;

        // update user margin
        $user_group['margin'] = $user_margin;
        $user_group['total'] = $net_total * $user_margin;

        // update overall total
        $overall_group['total'] = $net_total + $user_group['total'];
        $overall_group['margin'] = \floor($overall_group['total'] / $total_amount * 100.0) / 100.0;
        $overall_group['margin_amount'] = $overall_group['total'] - $total_amount;

        // update parameters
        $parameters['user_margin'] = (int) (100 * $user_margin);
    }

    /**
     * Creates groups from a calculation.
     *
     * @param Calculation $calculation the calculation to get groups from
     *
     * @return array an array with the computed values used to render the total view
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function createGroupsFromCalculation(Calculation $calculation): array
    {
        // empty?
        if ($calculation->isEmpty()) {
            return [$this->createEmptyGroup()];
        }

        $mapper = function (CalculationGroup $group): array {
            return [
                'id' => self::ROW_GROUP,
                'description' => $group->getCode(),
                'amount' => $group->getAmount(),
                'margin' => $group->getMargin(),
                'margin_amount' => $group->getMarginAmount(),
                'total' => $group->getTotal(),
            ];
        };

        $user_margin = $calculation->getUserMargin();
        $global_margin = $calculation->getGlobalMargin();
        $groups = $calculation->getGroups()->toArray();

        return $this->computeGroups($groups, $user_margin, $mapper, $global_margin);
    }

    /**
     * Creates groups from an array.
     *
     * @param array $source the form data as array
     *
     * @return array an array with the computed values used to render the total view
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function createGroupsFromData(array $source): array
    {
        // groupes?
        if (!$source_groups = $this->getArrayByKey($source, 'groups')) {
            return [
                'result' => true,
                'groups' => [$this->createEmptyGroup()],
                'overall_below' => false,
                'overall_margin' => 0,
                'overall_total' => 0,
            ];
        }

        // reduce groups
        /** @psalm-var array<int, float> $item_groups */
        $item_groups = \array_reduce($source_groups, function (array $carry, array $group): array {
            $id = (int) $group['group'];
            $carry[$id] = $this->reduceGroup($group);

            return $carry;
        }, []);

        // create groups
        $groups = [];
        foreach ($item_groups as $key => $value) {
            // check
            if (empty($value) || null === ($group = $this->getGroup($key))) {
                continue;
            }

            // create group if needed
            $id = (int) $group->getId();
            if (!\array_key_exists($id, $groups)) {
                $groups[$id] = [
                    'id' => self::ROW_GROUP,
                    'description' => $group->getCode(),
                    'amount' => 0,
                    'margin' => 0,
                    'margin_amount' => 0,
                    'total' => 0,
                ];
            }

            // update groupe
            $current = &$groups[$id];
            $amount = (float) $current['amount'] + $value;
            $margin = $this->getGroupMargin($group, $amount);
            $total = $this->round($margin * $amount);
            $margin_amount = $total - $amount;
            $current['amount'] = $amount;
            $current['margin'] = $margin;
            $current['margin_amount'] = $margin_amount;
            $current['total'] = $total;
        }

        // root groups?
        if (empty($groups)) {
            return [
                'result' => true,
                'groups' => [$this->createEmptyGroup()],
                'overall_below' => false,
                'overall_margin' => 0,
                'overall_total' => 0,
            ];
        }

        $user_margin = (float) $source['userMargin'] / 100.0;
        $groups = $this->computeGroups(groups: $groups, user_margin: $user_margin);
        $last_group = \end($groups);
        $overall_total = (float) $last_group['total'];
        $overall_margin = (float) $last_group['margin'];
        $overall_below = !$this->isFloatZero($overall_total) && $this->service->isMarginBelow($overall_margin);

        // OK
        return [
            'result' => true,
            'groups' => $groups,
            'overall_margin' => $overall_margin,
            'overall_total' => $overall_total,
            'overall_below' => $overall_below,
        ];
    }

    /**
     * Gets the minimum margin, in percent, for a calculation.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getMinMargin(): float
    {
        return $this->service->getMinMargin();
    }

    /**
     * Update the total of the given calculation.
     *
     * @param calculation $calculation the calculation to update
     *
     * @return bool true if updated; false otherwise
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function updateTotal(Calculation $calculation): bool
    {
        // save values
        $old_items_total = $this->round($calculation->getItemsTotal());
        $old_overall_total = $this->round($calculation->getOverallTotal());
        $old_global_margin = $this->round($calculation->getGlobalMargin());

        // 1. update each groups and compute item and overall total
        $items_total = 0;
        $overall_total = 0;
        $groups = $calculation->getGroups();
        foreach ($groups as $group) {
            $group->update();
            $items_total += $group->getAmount();
            $overall_total += $group->getTotal();
        }
        $items_total = $this->round($items_total);
        $overall_total = $this->round($overall_total);

        // 3. update global margin, net total and overall total
        $global_margin = $this->round($this->getGlobalMargin($overall_total));
        $overall_total = $this->round($overall_total * $global_margin);
        $overall_total = $this->round($overall_total * (1.0 + $calculation->getUserMargin()));

        // update if needed
        if ($old_items_total !== $items_total || $old_global_margin !== $global_margin || $old_overall_total !== $overall_total) {
            $calculation->setItemsTotal($items_total)
                ->setGlobalMargin($global_margin)
                ->setOverallTotal($overall_total);

            return true;
        }

        return false;
    }

    /**
     * Finds a groups for the given identifier.
     *
     * @param array<array> $groups the groups to search in
     * @param int          $id     the identifier to search for
     *
     * @return array the group, if found, a new empty group otherwise
     */
    private function &findGroup(array &$groups, int $id): array
    {
        foreach ($groups as &$group) {
            if ($group['id'] === $id) {
                return $group;
            }
        }

        // add empty
        $new_group = [
            'id' => $id,
            'amount' => 0.0,
            'margin' => 0.0,
            'margin_amount' => 0.0,
            'total' => 0.0,
            'description' => 'Unknown',
        ];
        $groups[] = $new_group;

        return $new_group;
    }

    /**
     * Creates calculation's total groups.
     *
     * @param array         $groups        the calculation groups
     * @param float         $user_margin   the user margin
     * @param callable|null $callback      the function to create a group lines
     * @param float|null    $global_margin the global margin or null to compute new global margin
     *
     * @return non-empty-array<array> the total groups
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function computeGroups(array $groups, float $user_margin, ?callable $callback = null, ?float $global_margin = null): array
    {
        // create group rows
        /**
         * @var array<array{
         *      id: int,
         *      description: string,
         *      amount: float,
         *      margin: float,
         *      margin_amount: float,
         *      total: float
         * }> $result
         */
        $result = $callback ? \array_map($callback, $groups) : $groups;

        // groups amount
        $groups_amount = $this->round($this->getGroupsAmount($result));

        // groups margin
        $groups_margin = $this->round($this->getGroupsMargin($result));

        // net total
        $total_net = $groups_amount + $groups_margin;

        // total groups row
        $result[] = [
            'id' => self::ROW_TOTAL_GROUP,
            'description' => $this->trans('calculation.fields.marginTotal'),
            'amount' => $groups_amount,
            'margin' => 1.0 + $this->round($this->safeDivide($groups_margin, $groups_amount)),
            'margin_amount' => $groups_margin,
            'total' => $total_net,
        ];

        // global margin row
        $global_margin ??= $this->getGlobalMargin($total_net);
        $global_amount = $this->round($total_net * ($global_margin - 1));
        $total_net += $global_amount;
        $result[] = [
            'id' => self::ROW_GLOBAL_MARGIN,
            'description' => $this->trans('calculation.fields.globalMargin'),
            'margin' => $global_margin,
            'total' => $global_amount,
        ];

        // total net row
        $result[] = [
            'id' => self::ROW_TOTAL_NET,
            'description' => $this->trans('calculation.fields.totalNet'),
            'total' => $total_net,
        ];

        // user margin row
        $user_amount = $this->round($total_net * $user_margin);
        $result[] = [
            'id' => self::ROW_USER_MARGIN,
            'description' => $this->trans('calculation.fields.userMargin'),
            'margin' => $user_margin,
            'total' => $user_amount,
        ];

        // overall total row
        $overall_total = $total_net + $user_amount;
        $overall_amount = $overall_total - $groups_amount;
        $overall_margin = $this->safeDivide($overall_amount, $groups_amount);
        $overall_margin = 1.0 + \floor($overall_margin * 100) / 100;
        $overall_below = !empty($groups) && !$this->isFloatZero($overall_total) && $this->service->isMarginBelow($overall_margin);

        $result[] = [
            'id' => self::ROW_OVERALL_TOTAL,
            'description' => $this->trans('calculation.fields.overallTotal'),
            'amount' => $groups_amount,
            'margin' => $overall_margin,
            'margin_amount' => $overall_amount,
            'total' => $overall_total,
            'overall_below' => $overall_below,
        ];

        return $result;
    }

    /**
     * Creates the group when no data is present.
     */
    private function createEmptyGroup(): array
    {
        return [
            'id' => self::ROW_EMPTY,
            'description' => $this->trans('calculation.edit.empty'),
        ];
    }

    private function getArrayByKey(array $array, string $key): ?array
    {
        return (\array_key_exists($key, $array) && !empty($array[$key])) ? (array) $array[$key] : null;
    }

    /**
     * Gets the global margin, in percent, for the given amount.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function getGlobalMargin(float $amount): float
    {
        if (!empty($amount)) {
            return $this->globalRepository->getMargin($amount);
        }

        return 0;
    }

    /**
     * Gets the group for the given identifier.
     */
    private function getGroup(int $id): ?Group
    {
        return $this->groupRepository->find($id);
    }

    /**
     * Gets the margin, in percent, for the given group and amount.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function getGroupMargin(Group $group, float $amount): float
    {
        if (!empty($amount)) {
            return $this->marginRepository->getMargin($group, $amount);
        }

        return 0;
    }

    /**
     * Gets the total amount of the groups.
     */
    private function getGroupsAmount(array $groups): float
    {
        return \array_reduce($groups, fn (float $carry, array $group): float => $carry + (float) $group['amount'], 0);
    }

    /**
     * Gets the total margin amount of the groups.
     */
    private function getGroupsMargin(array $groups): float
    {
        return \array_reduce($groups, fn (float $carry, array $group): float => $carry + (float) $group['margin_amount'], 0);
    }

    private function reduceCategory(array $category): float
    {
        if ($items = $this->getArrayByKey($category, 'items')) {
            return \array_reduce($items, fn (float $carry, array $item): float => $carry + ((float) $item['price'] * (float) $item['quantity']), 0);
        }

        return 0;
    }

    private function reduceGroup(array $group): float
    {
        if ($categories = $this->getArrayByKey($group, 'categories')) {
            return \array_reduce($categories, fn (float $carry, array $category): float => $carry + $this->reduceCategory($category), 0);
        }

        return 0;
    }
}
