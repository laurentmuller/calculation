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

namespace App\Report;

use App\Entity\Calculation;
use App\Entity\CalculationGroup;
use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Traits\TranslatorTrait;
use App\Utils\FormatUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Table to render the totals by group of a calculation.
 */
class CalculationTableGroups extends PdfTableBuilder
{
    use TranslatorTrait;

    private readonly Calculation $calculation;
    private readonly TranslatorInterface $translator;

    /**
     * Constructor.
     */
    public function __construct(CalculationReport $parent)
    {
        parent::__construct($parent);
        $this->translator = $parent->getTranslator();
        $this->calculation = $parent->getCalculation();
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Output totals by group.
     */
    public function output(): void
    {
        $groups = $this->calculation->getGroups();
        if ($groups->isEmpty()) {
            return;
        }

        $this->createColumns();
        foreach ($groups as $group) {
            $this->outputGroup($group);
        }
        $this->outputTotal($this->calculation);
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

    private function addAmount(float $number, PdfStyle $style = null): self
    {
        return $this->add(text: FormatUtils::formatAmount($number), style: $style);
    }

    private function addPercent(float $number, PdfStyle $style = null): self
    {
        return $this->add(text: FormatUtils::formatPercent($number), style: $style);
    }

    private function createColumns(): void
    {
        $columns = [
            PdfColumn::left($this->trans('report.calculation.resume'), 50),
            PdfColumn::right($this->trans('report.calculation.amount'), 20, true),
            PdfColumn::right($this->trans('report.calculation.margin_percent'), 20, true),
            PdfColumn::right($this->trans('report.calculation.margin_amount'), 20, true),
            PdfColumn::right($this->trans('report.calculation.total'), 20, true),
        ];
        $this->addColumns(...$columns);

        $this->startHeaderRow()
            ->add($columns[0]->getText())
            ->add($columns[1]->getText())
            ->add(text: $this->trans('report.calculation.margins'), cols: 2, alignment: PdfTextAlignment::CENTER)
            ->add($columns[4]->getText())
            ->endRow();
    }

    private function outputGroup(CalculationGroup $group): void
    {
        $this->startRow()
            ->add($group->getCode())
            ->addAmount($group->getAmount())
            ->addPercent($group->getMargin())
            ->addAmount($group->getMarginAmount())
            ->addAmount($group->getTotal())
            ->endRow();
    }

    private function outputTotal(Calculation $calculation): void
    {
        $style = PdfStyle::getHeaderStyle()->setFontRegular();
        $this->startHeaderRow()
            ->add($this->trans('calculation.fields.marginTotal'))
            ->addAmount($calculation->getGroupsAmount(), $style)
            ->addPercent($calculation->getGroupsMargin(), $style)
            ->addAmount($calculation->getGroupsMarginAmount(), $style)
            ->addAmount($calculation->getGroupsTotal())
            ->endRow();
    }
}
