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

namespace App\Report;

use App\Entity\Calculation;
use App\Entity\CalculationGroup;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Util\FormatUtils;
use Doctrine\Common\Collections\Collection;

/**
 * Table to render the totals by group of a calculation.
 *
 * @author Laurent Muller
 */
class CalculationTableGroups extends PdfTableBuilder
{
    /**
     * The calculation to render.
     */
    private Calculation $calculation;

    /**
     * Constructor.
     */
    public function __construct(CalculationReport $parent)
    {
        parent::__construct($parent);
        $this->calculation = $parent->getCalculation();
    }

    /**
     * Output totals by group.
     */
    public function output(): void
    {
        $calculation = $this->calculation;

        /** @var CalculationGroup[]|Collection $groups */
        /** @psalm-var Collection<int, CalculationGroup> $groups */
        $groups = $calculation->getGroups();
        if ($groups->isEmpty()) {
            return;
        }

        // style
        $style = PdfStyle::getHeaderStyle()->setFontRegular();

        // headers
        $columns = [
            PdfColumn::left($this->trans('report.calculation.resume'), 50),
            PdfColumn::right($this->trans('report.calculation.amount'), 20, true),
            PdfColumn::right($this->trans('report.calculation.margin_percent'), 20, true),
            PdfColumn::right($this->trans('report.calculation.margin_amount'), 20, true),
            PdfColumn::right($this->trans('report.calculation.total'), 20, true),
        ];
        $this->addColumns($columns);

        $this->startHeaderRow()
            ->add($columns[0]->getText())
            ->add($columns[1]->getText())
            ->add($this->trans('report.calculation.margins'), 2, null, self::ALIGN_CENTER)
            ->add($columns[4]->getText())
            ->endRow();

        // groups
        foreach ($groups as $group) {
            $this->startRow()
                ->add($group->getCode())
                ->add(FormatUtils::formatAmount($group->getAmount()))
                ->add(FormatUtils::formatPercent($group->getMargin()))
                ->add(FormatUtils::formatAmount($group->getMarginAmount()))
                ->add(FormatUtils::formatAmount($group->getTotal()))
                ->endRow();
        }

        // groups total
        $this->startHeaderRow()
            ->add($this->trans('calculation.fields.marginTotal'))
            ->add(FormatUtils::formatAmount($calculation->getGroupsAmount()), 1, $style)
            ->add(FormatUtils::formatPercent($calculation->getGroupsMargin()), 1, $style)
            ->add(FormatUtils::formatAmount($calculation->getGroupsMarginAmount()), 1, $style)
            ->add(FormatUtils::formatAmount($calculation->getGroupsTotal()))
            ->endRow();
    }

    /**
     * Render the table for the given report.
     */
    public static function render(CalculationReport $parent): self
    {
        $table = new self($parent);
        $table->output();

        return $table;
    }

    /**
     * Translates the given message.
     *
     * @param string $id the message id (may also be an object that can be cast to string)
     *
     * @return string the translated string
     *
     * @throws \InvalidArgumentException if the locale contains invalid characters
     */
    private function trans($id): string
    {
        /** @var AbstractReport $parent */
        $parent = $this->parent;

        return $parent->trans($id);
    }
}