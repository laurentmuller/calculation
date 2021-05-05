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

use Doctrine\Common\Collections\Criteria;

/**
 * Calculation table for duplicate items.
 *
 * @author Laurent Muller
 */
class CalculationDuplicateTable extends AbstractCalculationItemsTable
{
    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        return $this->repository->countDuplicateItems();
    }

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
     * {@inheritDoc}
     */
    public function getEmptyMessage(): string
    {
        return 'duplicate.empty';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntities(string $orderColumn = 'id', string $orderDirection = Criteria::DESC): array
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
