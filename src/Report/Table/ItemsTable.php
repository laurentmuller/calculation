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

namespace App\Report\Table;

use App\Entity\Calculation;
use App\Entity\CalculationCategory;
use App\Entity\CalculationGroup;
use App\Entity\CalculationItem;
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\Events\PdfGroupEvent;
use App\Pdf\Interfaces\PdfGroupListenerInterface;
use App\Pdf\PdfStyle;
use App\Report\CalculationReport;
use fpdf\PdfBorder;
use fpdf\PdfDocument;

/**
 * Table to render the groups, categories and items of calculation.
 */
class ItemsTable extends ReportGroupTable implements PdfGroupListenerInterface
{
    /**
     * The categories and items indent.
     */
    private const INDENT = 4;

    private readonly Calculation $calculation;
    private PdfStyle $groupStyle;
    private PdfStyle $rowStyle;

    public function __construct(CalculationReport $parent)
    {
        parent::__construct($parent);

        $this->calculation = $parent->getCalculation();
        $this->groupStyle = parent::getGroupStyle()
            ->setBorder(PdfBorder::leftRight());
        $this->rowStyle = PdfStyle::getCellStyle()
            ->setBorder(PdfBorder::leftRight());
    }

    public function drawGroup(PdfGroupEvent $event): true
    {
        /** @psalm-var CalculationGroup|CalculationCategory $key */
        $key = $event->getGroupKey();
        if ($key instanceof CalculationGroup) {
            return $this->renderGroup($key);
        }

        return $this->renderCategory($key);
    }

    /**
     * Render the table for the given calculation.
     */
    public static function render(CalculationReport $parent): self
    {
        $table = new self($parent);
        $table->output();

        return $table;
    }

    public function startRow(?PdfStyle $style = null): static
    {
        parent::startRow($style ?? $this->rowStyle);

        return $this;
    }

    private function addDescription(
        CalculationItem $item,
        array $duplicateItems,
        PdfStyle $defaultStyle,
        PdfStyle $errorStyle
    ): self {
        $style = \in_array($item, $duplicateItems, true) ? $errorStyle : $defaultStyle;
        $this->add($item->getDescription(), style: $style);

        return $this;
    }

    private function addStyledAmount(float $amount, ?PdfStyle $errorStyle = null): self
    {
        return $this->addCellAmount($amount, style: $this->isFloatZero($amount) ? $errorStyle : null);
    }

    private function checkLines(int $lines): void
    {
        $this->setInProgress(true);
        $this->checkNewPage((float) $lines * PdfDocument::LINE_HEIGHT);
        $this->setInProgress(false);
    }

    private function createColumns(): void
    {
        $this->addColumns(
            $this->leftColumn('calculationitem.fields.description', 50),
            $this->leftColumn('calculationitem.fields.unit', 20, true),
            $this->rightColumn('calculationitem.fields.price', 20, true),
            $this->rightColumn('calculationitem.fields.quantity', 20, true),
            $this->rightColumn('calculationitem.fields.total', 20, true)
        )->outputHeaders();
    }

    private function output(): void
    {
        $calculation = $this->calculation;
        $duplicateItems = $calculation->getDuplicateItems();

        $defaultStyle = PdfStyle::getCellStyle()
            ->setIndent(self::INDENT)
            ->setBorder(PdfBorder::leftRight());
        $errorStyle = (clone $defaultStyle)
            ->setTextColor(PdfTextColor::red());

        $this->createColumns();
        $this->setGroupListener($this);
        foreach ($calculation->getGroups() as $group) {
            $this->setGroupKey($group);
            foreach ($group->getCategories() as $category) {
                $this->setGroupKey($category);
                foreach ($category->getItems() as $item) {
                    $this->renderItem($item, $duplicateItems, $defaultStyle, $errorStyle);
                }
            }
        }
        $this->setInProgress(true);
        $this->renderTotal($calculation->getItemsTotal());
        $this->setInProgress(false);
        $this->getParent()->lineBreak(3);
    }

    private function renderCategory(CalculationCategory $category): true
    {
        $this->checkLines(2);
        $this->groupStyle->setIndent(self::INDENT / 2);
        $this->startRow($this->groupStyle)
            ->add($category->getCode())
            ->completeRow();

        return true;
    }

    private function renderGroup(CalculationGroup $group): true
    {
        $this->checkLines(3);
        $this->groupStyle->resetIndent();
        $this->startRow($this->groupStyle)
            ->add($group->getCode())
            ->completeRow();

        return true;
    }

    /**
     * @psalm-param CalculationItem[] $duplicateItems
     */
    private function renderItem(
        CalculationItem $item,
        array $duplicateItems,
        PdfStyle $defaultStyle,
        PdfStyle $errorStyle
    ): void {
        $this->startRow()
            ->addDescription($item, $duplicateItems, $defaultStyle, $errorStyle)
            ->add($item->getUnit())
            ->addStyledAmount($item->getPrice(), $errorStyle)
            ->addStyledAmount($item->getQuantity(), $errorStyle)
            ->addStyledAmount($item->getTotal())
            ->endRow();
    }

    private function renderTotal(float $total): void
    {
        $this->startHeaderRow()
            ->addCellTrans('calculation.fields.itemsTotal', 4)
            ->addCellAmount($total)
            ->endRow();
    }
}
