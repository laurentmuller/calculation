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

namespace App\Traits;

/**
 * Trait to format duplicate items for table, PDF report and Excel document.
 */
trait DuplicateItemsTrait
{
    /**
     * @psalm-param array<array{
     *          description: string,
     *          quantity: float,
     *          price: float,
     *          count: int}> $items
     */
    public function formatItems(array $items): string
    {
        $result = \array_map(fn (array $item): string => \sprintf('%s (%d)', $item['description'], $item['count']), $items);

        return \implode($this->getItemsSeparator(), $result);
    }

    /**
     * Gets the separator used to implode items.
     */
    protected function getItemsSeparator(): string
    {
        return "\n";
    }
}
