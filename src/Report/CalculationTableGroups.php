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
use App\Util\FormatUtils;
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

    /**
     * {@inheritDoc}
     */
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
            ->add(FormatUtils::formatAmount($group->getAmount()))
            ->add(FormatUtils::formatPercent($group->getMargin()))
            ->add(FormatUtils::formatAmount($group->getMarginAmount()))
            ->add(FormatUtils::formatAmount($group->getTotal()))
            ->endRow();
    }

    private function outputTotal(Calculation $calculation): void
    {
        $style = PdfStyle::getHeaderStyle()->setFontRegular();
        $this->startHeaderRow()
            ->add($this->trans('calculation.fields.marginTotal'))
            ->add(text: FormatUtils::formatAmount($calculation->getGroupsAmount()), style: $style)
            ->add(text: FormatUtils::formatPercent($calculation->getGroupsMargin()), style: $style)
            ->add(text: FormatUtils::formatAmount($calculation->getGroupsMarginAmount()), style: $style)
            ->add(FormatUtils::formatAmount($calculation->getGroupsTotal()))
            ->endRow();
    }
}
