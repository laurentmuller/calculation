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
use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\PdfColumn;
use App\Pdf\PdfDocument;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Pdf\PdfTextColor;
use App\Traits\TranslatorTrait;
use App\Util\FormatUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Table to render the overall totals of a calculation.
 */
class CalculationTableOverall extends PdfTableBuilder
{
    use TranslatorTrait;

    private readonly Calculation $calculation;
    private readonly float $minMargin;
    private readonly TranslatorInterface $translator;

    /**
     * Constructor.
     */
    public function __construct(CalculationReport $parent)
    {
        parent::__construct($parent);
        $this->translator = $parent->getTranslator();
        $this->calculation = $parent->getCalculation();
        $this->minMargin = $parent->getMinMargin();
    }

    /**
     * {@inheritDoc}
     */
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Output overall totals.
     */
    public function output(): void
    {
        $parent = $this->getParent();
        $calculation = $this->calculation;
        $totalItems = $calculation->getGroupsAmount();
        $totalMargins = $calculation->getGroupsMarginAmount();
        $totalBrut = $totalItems + $totalMargins;
        $globalMargin = $calculation->getGlobalMargin();
        $globalAmount = $totalBrut * ($globalMargin - 1.0);
        $totalNet = $totalBrut + $globalAmount;
        $userMargin = $calculation->getUserMargin();
        $userAmount = $totalNet * $userMargin;

        $this->createColumns()
            ->outputGlobalMargin($globalMargin, $globalAmount)
            ->outputUserMargin($userMargin, $userAmount, $totalNet)
            ->outputOverallTotal($parent, $calculation, $totalItems)
            ->outputTimestampable($parent, $calculation);
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

    private function addAmount(float $number, int $cols = 1): self
    {
        return $this->add(FormatUtils::formatAmount($number), $cols);
    }

    private function addPercent(float $number, ?PdfStyle $style = null): self
    {
        return $this->add(text: FormatUtils::formatPercent($number), style: $style);
    }

    private function createColumns(): self
    {
        return $this->addColumns(
            PdfColumn::left(null, 50),
            PdfColumn::right(null, 20, true),
            PdfColumn::right(null, 20, true),
            PdfColumn::right(null, 20, true),
            PdfColumn::right(null, 20, true)
        )->setRepeatHeader(false);
    }

    private function outputGlobalMargin(float $globalMargin, float $globalAmount): self
    {
        return $this->startRow()
            ->add($this->trans('calculation.fields.globalMargin'), 2)
            ->addPercent($globalMargin)
            ->addAmount($globalAmount, 2)
            ->endRow();
    }

    private function outputOverallTotal(PdfDocument $parent, Calculation $calculation, float $totalItems): self
    {
        $style = null;
        if ($calculation->isMarginBelow($this->minMargin)) {
            $style = PdfStyle::getHeaderStyle()->setTextColor(PdfTextColor::red());
        }
        $this->startHeaderRow()
            ->add($this->trans('calculation.fields.overallTotal'))
            ->addAmount($totalItems)
            ->addPercent($calculation->getOverallMargin(), $style)
            ->addAmount($calculation->getOverallMarginAmount())
            ->addAmount($calculation->getOverallTotal())
            ->endRow();
        $parent->Ln(1);

        return $this;
    }

    private function outputTimestampable(PdfDocument $parent, Calculation $calculation): void
    {
        $style = PdfStyle::getNoBorderStyle()
            ->setFontItalic()
            ->setFontSize(7);
        $oldMargins = $parent->setCellMargin(0);
        $created = $calculation->getCreatedText($this->translator);
        $updated = $calculation->getUpdatedText($this->translator);
        $this->startRow()
            ->add($created, 1, $style, PdfTextAlignment::LEFT)
            ->add($updated, 4, $style, PdfTextAlignment::RIGHT)
            ->endRow();
        $parent->setCellMargin($oldMargins);
    }

    private function outputUserMargin(float $userMargin, float $userAmount, float $totalNet): self
    {
        if (!empty($userMargin)) {
            $this->startHeaderRow()
                ->add($this->trans('calculation.fields.totalNet'), 4)
                ->addAmount($totalNet)
                ->endRow();
            $this->startRow()
                ->add($this->trans('calculation.fields.userMargin'), 2)
                ->addPercent($userMargin)
                ->addAmount($userAmount, 2)
                ->endRow();
        }

        return $this;
    }
}
