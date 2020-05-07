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

namespace App\Report;

use App\Entity\Calculation;
use App\Entity\CalculationItem;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTextColor;

/**
 * Render the calculation groups and items.
 *
 * @author Laurent Muller
 *
 * @see \App\Entity\CalculationGroup
 * @see \App\Entity\CalculationItem
 */
class CalculationGroupTable extends PdfGroupTableBuilder
{
    /**
     * Constructor.
     *
     * @param CalculationReport $parent the parent document to print in
     */
    public function __construct(CalculationReport $parent)
    {
        parent::__construct($parent, true);
    }

    /**
     * Output the given calculation.
     *
     * @param Calculation $calculation the calculation to output
     */
    public function output(Calculation $calculation): void
    {
        // groups
        $groups = $calculation->getGroups();
        if ($groups->isEmpty()) {
            return;
        }

        /** @var CalculationReport $parent */
        $parent = $this->parent;
        $duplicateItems = $calculation->getDuplicateItems();

        // styles
        $color = PdfTextColor::red();
        $defaultStyle = PdfStyle::getCellStyle()->setIndent(2);
        $errorStyle = (clone $defaultStyle)->setTextColor($color);

        // headers
        $columns = [
            PdfColumn::left($this->trans('calculationitem.fields.description'), 50),
            PdfColumn::left($this->trans('calculationitem.fields.unit'), 20, true),
            PdfColumn::right($this->trans('calculationitem.fields.price'), 20, true),
            PdfColumn::right($this->trans('calculationitem.fields.quantity'), 20, true),
            PdfColumn::right($this->trans('calculationitem.fields.total'), 20, true),
        ];
        $this->addColumns($columns)
            ->outputHeaders();

        // render
        foreach ($groups as $group) {
            $this->setGroupName($group->getCode());
            foreach ($group->getItems() as $item) {
                $this->startRow();
                $this->addDescription($item, $duplicateItems, $defaultStyle, $errorStyle);
                $this->add($item->getUnit());
                $this->addAmount($item->getPrice(), $errorStyle);
                $this->addAmount($item->getQuantity(), $errorStyle);
                $this->addAmount($item->getTotal(), null);
                $this->endRow();
            }
        }

        // total
        $total = $calculation->getItemsTotal();
        $this->startHeaderRow()
            ->add($this->trans('calculation.fields.itemsTotal'), 4)
            ->add($parent->localeAmount($total))
            ->endRow();
    }

    /**
     * Render the table for the given calculation.
     *
     * @param CalculationReport $parent the parent document to print in
     */
    public static function render(CalculationReport $parent): void
    {
        $table = new self($parent);
        $table->output($parent->getCalculation());
    }

    /**
     * Adds formatted amount with an error style if the amount is equal to 0.
     *
     * @param float    $amount     the amount to output
     * @param PdfStyle $errorStyle the error style to use when amount is equal to 0
     */
    private function addAmount(float $amount, ?PdfStyle $errorStyle): self
    {
        $style = empty($amount) ? $errorStyle : null;
        $text = $this->parent->localeAmount($amount);

        return $this->add($text, 1, $style);
    }

    /**
     * Adds descriptoin with an error style if duplicate.
     *
     * @param CalculationItem $item           the item to get description for
     * @param array           $duplicateItems the duplicate items
     * @param PdfStyle        $defaultStyle   the style to use if item is not duplicate
     * @param PdfStyle        $errorStyle     the style to use when item is duplicate
     */
    private function addDescription(CalculationItem $item, array $duplicateItems, PdfStyle $defaultStyle, PdfStyle $errorStyle): self
    {
        $style = \in_array($item, $duplicateItems, true) ? $errorStyle : $defaultStyle;

        return $this->add($item->getDescription(), 1, $style);
    }

    /**
     * Translate a string.
     *
     * @param string $key The key
     *
     * @return string The translated key
     */
    private function trans(string $key): string
    {
        /** @var BaseReport $parent */
        $parent = $this->parent;

        return $parent->trans($key);
    }
}
