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
 * Trait to format empty items for the table, the PDF report, and the Excel document.
 *
 * @psalm-import-type CalculationItemEntry from \App\Repository\CalculationRepository
 */
trait EmptyItemsTrait
{
    use MathTrait;

    /**
     * @psalm-param CalculationItemEntry[] $items
     */
    public function formatItems(array $items): string
    {
        if ([] === $items) {
            return '';
        }

        $priceLabel = $this->getPriceLabel();
        $quantityLabel = $this->getQuantityLabel();
        $result = \array_map(function (array $item) use ($priceLabel, $quantityLabel): string {
            $founds = [];
            if ($this->isFloatZero($item['price'])) {
                $founds[] = $priceLabel;
            }
            if ($this->isFloatZero($item['quantity'])) {
                $founds[] = $quantityLabel;
            }

            return \sprintf('%s (%s)', $item['description'], \implode(', ', $founds));
        }, $items);

        return \implode($this->getItemsSeparator(), $result);
    }

    /**
     * Gets the separator used to implode items.
     */
    protected function getItemsSeparator(): string
    {
        return StringUtils::NEW_LINE;
    }

    abstract protected function getPriceLabel(): string;

    abstract protected function getQuantityLabel(): string;
}
