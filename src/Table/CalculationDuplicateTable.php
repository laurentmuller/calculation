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

namespace App\Table;

use App\Traits\DuplicateItemsTrait;

/**
 * Calculation table for duplicate items.
 *
 * @phpstan-import-type CalculationItemType from \App\Repository\CalculationRepository
 */
class CalculationDuplicateTable extends AbstractCalculationItemsTable
{
    use DuplicateItemsTrait;

    /**
     * @return int<0, max>
     */
    #[\Override]
    public function count(): int
    {
        return $this->repository->countItemsDuplicate();
    }

    #[\Override]
    public function getEmptyMessage(): ?string
    {
        return 0 === $this->count() ? 'duplicate.empty' : null;
    }

    /**
     * @phpstan-param self::SORT_* $orderDirection
     *
     * @phpstan-return CalculationItemType[]
     */
    #[\Override]
    protected function getEntities(string $orderColumn = 'id', string $orderDirection = self::SORT_DESC): array
    {
        return $this->repository->getItemsDuplicate($orderColumn, $orderDirection);
    }

    /**
     * @phpstan-param CalculationItemType[] $items
     */
    #[\Override]
    protected function getItemsCount(array $items): int
    {
        return \array_reduce(
            $items,
            /** @phpstan-param CalculationItemType $item */
            function (int $carry, array $item): int {
                foreach ($item['items'] as $child) {
                    $carry += $child['count'];
                }

                return $carry;
            },
            0
        );
    }

    /**
     * Gets the separator used to implode items.
     */
    protected function getItemsSeparator(): string
    {
        return '<br>';
    }
}
