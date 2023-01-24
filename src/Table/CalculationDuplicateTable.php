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
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Exception\ORMException;

/**
 * Calculation table for duplicate items.
 */
class CalculationDuplicateTable extends AbstractCalculationItemsTable
{
    use DuplicateItemsTrait;

    /**
     * {@inheritdoc}
     *
     * @throws ORMException
     */
    public function count(): int
    {
        return $this->repository->countDuplicateItems();
    }

    /**
     * {@inheritDoc}
     *
     * @throws ORMException
     */
    public function getEmptyMessage(): ?string
    {
        return 0 === $this->count() ? 'duplicate.empty' : null;
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
            /** @psalm-var array{count: int} $child*/
            foreach ($item['items'] as $child) {
                $carry += $child['count'];
            }

            return $carry;
        }, 0);
    }

    /**
     * Gets the separator used to implode items.
     */
    protected function getItemsSeparator(): string
    {
        return '<br>';
    }
}
