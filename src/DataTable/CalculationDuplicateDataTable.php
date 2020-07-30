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

namespace App\DataTable;

use App\Repository\CalculationRepository;

/**
 * Data table handler for calculations with duplicate items.
 *
 * @author Laurent Muller
 */
class CalculationDuplicateDataTable extends CalculationItemsDataTable
{
    /**
     * The datatable identifier.
     */
    public const ID = 'Calculation.duplicate';

    /**
     * {@inheritdoc}
     */
    public function formatInvalidItems(array $items): string
    {
        $result = \array_map(function (array $item) {
            return \sprintf('%s (%d)', $item['description'], $item['count']);
        }, $items);

        return \implode('<br>', $result);
    }

    /**
     * {@inheritdoc}
     */
    protected function computeItemsCount(array $items): int
    {
        return \array_reduce($items, function (int $carry, array $item) {
            foreach ($item['items'] as $child) {
                $carry += $child['count'];
            }

            return $carry;
        }, 0);
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems(CalculationRepository $repository, string $orderColumn, string $orderDirection): array
    {
        return $repository->getDuplicateItems($orderColumn, $orderDirection);
    }
}
