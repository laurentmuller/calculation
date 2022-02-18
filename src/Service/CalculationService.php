<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Service;

use App\Entity\Calculation;
use App\Entity\CalculationGroup;
use App\Entity\GlobalMargin;
use App\Entity\Group;
use App\Entity\GroupMargin;
use App\Repository\GlobalMarginRepository;
use App\Traits\MathTrait;
use App\Traits\TranslatorTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to update calculation totals.
 *
 * @author Laurent Muller
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

    private EntityManagerInterface $manager;

    private ApplicationService $service;

    /**
     * Constructor.
     */
    public function __construct(EntityManagerInterface $manager, ApplicationService $service, TranslatorInterface $translator)
    {
        $this->manager = $manager;
        $this->service = $service;
        $this->setTranslator($translator);
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
        /** @psalm-var array $groups */
        $groups = &$parameters['groups'];
        $totalGroup = &$this->findGroup($groups, self::ROW_TOTAL_GROUP);
        $netGroup = &$this->findGroup($groups, self::ROW_TOTAL_NET);
        $userGroup = &$this->findGroup($groups, self::ROW_USER_MARGIN);
        $overallGroup = &$this->findGroup($groups, self::ROW_OVERALL_TOTAL);

        // get values
        $minMargin = (float) $parameters['min_margin'];
        $totalAmount = (float) $totalGroup['amount'];
        $netTotal = (float) $netGroup['total'];

        // net total?
        if ($this->isFloatZero($netTotal)) {
            return;
        }

        // compute user margin to reach minimum and round up
        $userMargin = (($totalAmount * $minMargin) - $netTotal) / $netTotal;
        $userMargin = \ceil($userMargin * 100.0) / 100.0;

        // update user margin
        $userGroup['margin'] = $userMargin;
        $userGroup['total'] = $netTotal * $userMargin;

        // update overall total
        $overallGroup['total'] = $netTotal + $userGroup['total'];
        $overallGroup['margin'] = \floor($overallGroup['total'] / $totalAmount * 100.0) / 100.0;
        $overallGroup['margin_amount'] = $overallGroup['total'] - $totalAmount;

        // update parameters
        $parameters['user_margin'] = (int) (100 * $userMargin);
    }

    /**
     * Creates groups from a calculation.
     *
     * @param Calculation $calculation the calculation to get groups from
     *
     * @return array an array with the computed values used to render the total view
     */
    public function createGroupsFromCalculation(Calculation $calculation): array
    {
        //empty?
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

        $userMargin = $calculation->getUserMargin();
        $globalMargin = $calculation->getGlobalMargin();
        $groups = $calculation->getGroups()->toArray();

        return $this->computeGroups($groups, $mapper, $userMargin, $globalMargin);
    }

    /**
     * Creates groups from an array.
     *
     * @param array $source the form data as array
     *
     * @return array an array with the computed values used to render the total view
     */
    public function createGroupsFromData(array $source): array
    {
        // groups?
        if (!$this->isArrayKey($source, 'groups')) {
            return [
                'result' => true,
                'groups' => [$this->createEmptyGroup()],
                'overall_below' => false,
                'overall_margin' => 0,
                'overall_total' => 0,
            ];
        }

        // reduce groups
        /** @psalm-var array<int, float> $itemGroups */
        $itemGroups = \array_reduce((array) $source['groups'], function (array $carry, array $group): array {
            $id = (int) ($group['group']);
            $carry[$id] = $this->reduceGroup($group);

            return $carry;
        }, []);

        // create groups
        $groups = [];
        foreach ($itemGroups as $key => $value) {
            if (empty($value)) {
                continue;
            }

            $group = $this->getGroup($key);
            if (!$group instanceof Group) {
                continue;
            }

            //create group if needed
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

            // update group
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

        // sort
        \uasort($groups, static function (array $a, array $b) {
            return $a['description'] <=> $b['description'];
        });

        $userMargin = (float) $source['userMargin'] / 100.0;
        $groups = $this->computeGroups($groups, null, $userMargin);
        /** @psalm-var array $lastGroup */
        $lastGroup = \end($groups);
        $overall_margin = (float) $lastGroup['margin'];
        $overall_total = (float) $lastGroup['total'];
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
     */
    public function getMinMargin(): float
    {
        return $this->service->getMinMargin();
    }

    /**
     * Gets the translator.
     */
    public function getTranslator(): ?TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Update the total of the given calculation.
     *
     * @param calculation $calculation the calculation to update
     *
     * @return bool true if updated; false otherwise
     */
    public function updateTotal(Calculation $calculation): bool
    {
        // save values
        $oldItemsTotal = $this->round($calculation->getItemsTotal());
        $oldOverallTotal = $this->round($calculation->getOverallTotal());
        $oldGlobalMargin = $this->round($calculation->getGlobalMargin());

        /** @var CalculationGroup[] $groups */
        $groups = $calculation->getGroups();

        // 1. udpate each groups
        foreach ($groups as $group) {
            $group->update();
        }

        // 2. compute item and overall total
        $itemsTotal = 0;
        $overallTotal = 0;
        foreach ($groups as $group) {
            $overallTotal += $group->getTotal();
            $itemsTotal += $group->getAmount();
        }

        // 3. update global margin, net total and overall total
        $globalMargin = $this->round($this->getGlobalMargin($overallTotal));
        $overallTotal *= $globalMargin;
        $overallTotal *= (1 + $calculation->getUserMargin());

        // round
        $overallTotal = $this->round($overallTotal);
        $itemsTotal = $this->round($itemsTotal);

        // update if needed
        if ($oldItemsTotal !== $itemsTotal || $oldGlobalMargin !== $globalMargin || $oldOverallTotal !== $overallTotal) {
            $calculation->setItemsTotal($itemsTotal)
                ->setGlobalMargin($globalMargin)
                ->setOverallTotal($overallTotal);

            return true;
        }

        return false;
    }

    /**
     * Finds a groups for the given identifier.
     *
     * @param array $groups the groups to search in
     * @param int   $id     the identifier to search for
     *
     * @return array the group, if found, a new empty group otherwise
     */
    private function &findGroup(array &$groups, int $id): array
    {
        /** @psalm-var array $group */
        foreach ($groups as &$group) {
            if ($group['id'] === $id) {
                return $group;
            }
        }

        // add emtpy
        $group = [
            'id' => $id,
            'amount' => 0.0,
            'margin' => 0.0,
            'margin_amount' => 0.0,
            'total' => 0.0,
            'description' => 'Unknown',
        ];
        $groups[] = $group;

        return $group;
    }

    /**
     * Creates calculation's total groups.
     *
     * @param array    $groups       the calculation groups
     * @param callable $callback     the function to create a group lines
     * @param float    $userMargin   the user margin
     * @param float    $globalMargin the global margin or null to compute new global margin
     *
     * @return array the total groups
     */
    private function computeGroups(array $groups, ?callable $callback, float $userMargin, ?float $globalMargin = null): array
    {
        // create group rows
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
        $globalmargin_percent = $globalMargin ?: $this->getGlobalMargin($total_net);
        $globalmargin_amount = $this->round($total_net * ($globalmargin_percent - 1));
        $total_net += $globalmargin_amount;
        $result[] = [
            'id' => self::ROW_GLOBAL_MARGIN,
            'description' => $this->trans('calculation.fields.globalMargin'),
            'margin' => $globalmargin_percent,
            'total' => $globalmargin_amount,
        ];

        // total net row
        $result[] = [
            'id' => self::ROW_TOTAL_NET,
            'description' => $this->trans('calculation.fields.totalNet'),
            'total' => $total_net,
        ];

        // user margin row
        $usermargin_amount = $this->round($total_net * $userMargin);
        $result[] = [
            'id' => self::ROW_USER_MARGIN,
            'description' => $this->trans('calculation.fields.userMargin'),
            'margin' => $userMargin,
            'total' => $usermargin_amount,
        ];

        // overall total row
        $overall_total = $total_net + $usermargin_amount;
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

    /**
     * Gets the global margin, in percent, for the given amount.
     *
     * @param float $amount the amount to get margin for
     *
     * @return float the margin in percent
     * @psalm-suppress UnnecessaryVarAnnotation
     */
    private function getGlobalMargin(float $amount): float
    {
        if (!empty($amount)) {
            /** @var GlobalMarginRepository $repository */
            $repository = $this->manager->getRepository(GlobalMargin::class);

            return $repository->getMargin($amount);
        }

        return 0;
    }

    /**
     * Gets the group for the given identifier.
     *
     * @param int $id the group identifier
     */
    private function getGroup(int $id): ?Group
    {
        /** @psalm-var \Doctrine\ORM\EntityRepository<Group> $repository */
        $repository = $this->manager->getRepository(Group::class);

        return $repository->find($id);
    }

    /**
     * Gets the margin, in percent, for the given group and amount.
     *
     * @param Group $group  the group
     * @param float $amount the amount to get percent for
     *
     * @return float the margin, in percent, if found; 0 otherwise
     * @psalm-suppress UnnecessaryVarAnnotation
     */
    private function getGroupMargin(Group $group, float $amount): float
    {
        if (!empty($amount)) {
            /** @var \App\Repository\GroupMarginRepository $repository */
            $repository = $this->manager->getRepository(GroupMargin::class);

            return $repository->getMargin($group, $amount);
        }

        return 0;
    }

    /**
     * Gets the total amount of the groups.
     *
     * @param array $groups the groups
     */
    private function getGroupsAmount(array $groups): float
    {
        return \array_reduce($groups, function (float $carry, array $group): float {
            return $carry + (float) $group['amount'];
        }, 0);
    }

    /**
     * Gets the total margin amount of the groups.
     *
     * @param array $groups the groups
     */
    private function getGroupsMargin(array $groups): float
    {
        return \array_reduce($groups, function (float $carry, array $group): float {
            return $carry + (float) $group['margin_amount'];
        }, 0);
    }

    private function isArrayKey(array $array, string $key): bool
    {
        return \array_key_exists($key, $array) && !empty($array[$key]);
    }

    private function reduceCategory(array $category): float
    {
        if ($this->isArrayKey($category, 'items')) {
            /** @psalm-var array $items */
            $items = $category['items'];

            return \array_reduce($items, function (float $carry, array $item): float {
                $price = (float) $item['price'];
                $quantity = (float) $item['quantity'];

                return $carry + ($price * $quantity);
            }, 0);
        }

        return 0;
    }

    private function reduceGroup(array $group): float
    {
        if ($this->isArrayKey($group, 'categories')) {
            /** @psalm-var array $categories */
            $categories = $group['categories'];

            return \array_reduce($categories, function (float $carry, array $category) {
                return $carry + $this->reduceCategory($category);
            }, 0);
        }

        return 0;
    }
}
