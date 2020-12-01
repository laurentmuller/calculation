<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Service;

use App\Entity\Calculation;
use App\Entity\CalculationGroup;
use App\Entity\Category;
use App\Entity\CategoryMargin;
use App\Entity\GlobalMargin;
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

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * @var ApplicationService
     */
    private $service;

    /**
     * Constructor.
     */
    public function __construct(EntityManagerInterface $manager, ApplicationService $service, TranslatorInterface $translator)
    {
        $this->manager = $manager;
        $this->service = $service;
        $this->translator = $translator;
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

        $mapper = function (CalculationGroup $group) {
            return [
                'id' => self::ROW_GROUP,
                'amount' => $group->getAmount(),
                'margin' => $group->getMargin(),
                'margin_amount' => $group->getMarginAmount(),
                'total' => $group->getTotal(),
                'description' => $group->getCode(),
            ];
        };

        $userMargin = $calculation->getUserMargin();
        $globalMargin = $calculation->getGlobalMargin();
        $groups = $calculation->getRootGroups()->toArray();

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
        if (!\array_key_exists('groups', $source) || empty($source['groups'])) {
            return [
                'result' => true,
                'groups' => [$this->createEmptyGroup()],
                'overall_below' => false,
                'overall_margin' => 0,
                'overall_total' => 0,
            ];
        }

        // reduce items groups
        $itemGroups = \array_reduce($source['groups'], function (array $carry, array $group) {
            $id = (int) ($group['categoryId']);
            $carry[$id] = $this->reduceGroupItems($group);

            return $carry;
        }, []);

        // create root groups
        $groups = [];
        foreach ($itemGroups as $key => $value) {
            if (empty($value)) {
                continue;
            }

            $category = $this->getCategory($key);
            if (null !== $category && null !== $category->getParent()) {
                $parent = $category->getParent();
                $id = $parent->getId();

                //create group if needed
                if (!\array_key_exists($id, $groups)) {
                    $groups[$id] = [
                        'id' => self::ROW_GROUP,
                        'amount' => 0,
                        'margin' => 0,
                        'margin_amount' => 0,
                        'total' => 0,
                        'description' => $parent->getCode(),
                    ];
                }

                // update group
                $parentGroup = &$groups[$id];
                $amount = $parentGroup['amount'] + $value;
                $margin = $this->getCategoryMargin($id, $amount);
                $total = $this->round($margin * $amount);
                $margin_amount = $total - $amount;
                $parentGroup['amount'] = $amount;
                $parentGroup['margin'] = $margin;
                $parentGroup['margin_amount'] = $margin_amount;
                $parentGroup['total'] = $total;
            }
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
        \uasort($groups, function (array $a, array $b) {
            return $a['description'] <=> $b['description'];
        });

        $userMargin = (float) $source['userMargin'] / 100.0;
        $groups = $this->computeGroups($groups, null, $userMargin);
        $overall_margin = \end($groups)['margin'];
        $overall_total = \end($groups)['total'];
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
    public function getTranslator(): TranslatorInterface
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

        // 2. add missing root groups
        foreach ($groups as $group) {
            $category = $group->getParentCategory();
            if (null === $category) {
                continue;
            }
            $parentGroup = $calculation->findGroup($category, true);
            if (null === $parentGroup) {
                continue;
            }

            // update
            $amount = $parentGroup->getAmount() + $group->getAmount();
            $margin = $category->findPercent($amount);
            $parentGroup->setAmount($amount)->setMargin($margin);
        }

        // 3. compute item and overall total
        $itemsTotal = 0;
        $overallTotal = 0;
        foreach ($groups as $group) {
            if ($group->isRootGroup()) {
                $overallTotal += $group->getTotal();
            } else {
                $itemsTotal += $group->getAmount();
            }
        }

        // 4. update global margin, net total and overall total
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
            'amount' => $groups_amount,
            'margin' => 1.0 + $this->round($this->safeDivide($groups_margin, $groups_amount)),
            'margin_amount' => $groups_margin,
            'total' => $total_net,
            'description' => $this->trans('calculation.fields.marginTotal'),
        ];

        // global margin row
        $globalmargin_percent = $globalMargin ?: $this->getGlobalMargin($total_net);
        $globalmargin_amount = $this->round($total_net * ($globalmargin_percent - 1));
        $total_net += $globalmargin_amount;
        $result[] = [
            'id' => self::ROW_GLOBAL_MARGIN,
            'margin' => $globalmargin_percent,
            'total' => $globalmargin_amount,
            'description' => $this->trans('calculation.fields.globalMargin'),
        ];

        // total net row
        $result[] = [
            'id' => self::ROW_TOTAL_NET,
            'total' => $total_net,
            'description' => $this->trans('calculation.fields.totalNet'),
        ];

        // user margin row
        $usermargin_amount = $this->round($total_net * $userMargin);
        $result[] = [
            'id' => self::ROW_USER_MARGIN,
            'margin' => $userMargin,
            'total' => $usermargin_amount,
            'description' => $this->trans('calculation.fields.userMargin'),
        ];

        // overall total row
        $overall_total = $total_net + $usermargin_amount;
        $overall_amount = $overall_total - $groups_amount;
        $overall_margin = 1.0 + $this->round($this->safeDivide($overall_amount, $groups_amount));
        $overall_below = !empty($groups) && !$this->isFloatZero($overall_total) && $this->service->isMarginBelow($overall_margin);

        $result[] = [
            'id' => self::ROW_OVERALL_TOTAL,
            'amount' => $groups_amount,
            'margin' => $overall_margin,
            'margin_amount' => $overall_amount,
            'total' => $overall_total,
            'description' => $this->trans('calculation.fields.overallTotal'),
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
     * Gets the category for the given identifier.
     *
     * @param int $id the category identifier
     */
    private function getCategory(int $id): ?Category
    {
        return $this->manager->getRepository(Category::class)->find($id);
    }

    /**
     * Gets the margin, in percent, for the given category and amount.
     *
     * @param int   $id     the category identifier
     * @param float $amount the amount to get percent for
     *
     * @return float the margin, in percent, if found; 0 otherwise
     */
    private function getCategoryMargin(int $id, float $amount): float
    {
        if ($amount) {
            /** @var \App\Repository\CategoryMarginRepository $repository */
            $repository = $this->manager->getRepository(CategoryMargin::class);

            return (float) ($repository->getMargin($id, $amount));
        }

        return 0;
    }

    /**
     * Gets the global margin, in percent, for the given amount.
     *
     * @param float $amount the amount to get margin for
     *
     * @return float the margin in percent
     */
    private function getGlobalMargin(float $amount): float
    {
        if ($amount) {
            /** @var \App\Repository\GlobalMarginRepository $repository */
            $repository = $this->manager->getRepository(GlobalMargin::class);

            return (float) $repository->getMargin($amount);
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
        return \array_reduce($groups, function (float $carry, array $group) {
            return $carry + $group['amount'];
        }, 0);
    }

    /**
     * Gets the total margin amount of the groups.
     *
     * @param array $groups the groups
     */
    private function getGroupsMargin(array $groups): float
    {
        return \array_reduce($groups, function (float $carry, array $group) {
            return $carry + $group['margin_amount'];
        }, 0);
    }

    private function reduceGroupItems(array $group): float
    {
        if (\array_key_exists('items', $group)) {
            return \array_reduce($group['items'], function (float $carry, array $item) {
                $price = $item['price']; //(float)
                $quantity = $item['quantity']; //(float)
                $total = $carry + ($price * $quantity);

                return $total;
            }, 0);
        }

        return 0;
    }
}
