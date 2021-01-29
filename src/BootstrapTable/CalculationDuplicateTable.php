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

namespace App\BootstrapTable;

/**
 * Calculation table for duplicate items.
 *
 * @author Laurent Muller
 */
class CalculationDuplicateTable extends AbstractCalculationItemsTable
{
    /**
     * {@inheritdoc}
     */
    public function formatItems(array $items): string
    {
        $result = \array_map(function (array $item) {
            return \sprintf('%s (%d)', $item['description'], $item['count']);
        }, $items);

        return \implode('<br>', $result);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntities(string $orderColumn, string $orderDirection): array
    {
        return $this->repository->getDuplicateItems($orderColumn, $orderDirection);
    }

    /**
     * {@inheritdoc}
     */
    protected function getItemsCount(array $items): int
    {
        return \array_reduce($items, function (int $carry, array $item) {
            foreach ($item['items'] as $child) {
                $carry += $child['count'];
            }

            return $carry;
        }, 0);
    }
}
