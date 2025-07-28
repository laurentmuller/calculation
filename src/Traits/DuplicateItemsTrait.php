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

use App\Utils\StringUtils;

/**
 * Trait to format duplicate items for table, PDF report and Excel document.
 *
 * @phpstan-import-type CalculationItemEntry from \App\Repository\CalculationRepository
 */
trait DuplicateItemsTrait
{
    /**
     * @phpstan-param CalculationItemEntry[] $items
     */
    public function formatItems(array $items): string
    {
        if ([] === $items) {
            return '';
        }

        $result = \array_map(
            static fn (array $item): string => \sprintf('%s (%d)', $item['description'], $item['count']),
            $items
        );

        return \implode($this->getItemsSeparator(), $result);
    }

    /**
     * Gets the separator used to implode items.
     */
    protected function getItemsSeparator(): string
    {
        return StringUtils::NEW_LINE;
    }
}
