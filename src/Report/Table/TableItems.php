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
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Report\CalculationReport;
use fpdf\PdfBorder;
use fpdf\PdfDocument;

/**
 * Table to render the groups, categories and items of a calculation.
 */
class TableItems extends ReportGroupTable
{
    /**
     * The categories and items indent.
     */
    private const INDENT = 4;

    private readonly Calculation $calculation;

    public function __construct(CalculationReport $parent)
    {
        parent::__construct($parent, $parent->getTranslator());
        $this->calculation = $parent->getCalculation();
    }

    public function getGroupStyle(): PdfStyle
    {
        $style = parent::getGroupStyle();
        if (!$style instanceof PdfStyle) {
            $style = PdfStyle::getCellStyle()->setFontBold();
        }
        $style->setBorder(PdfBorder::leftRight());

        return $style;
    }

    /**
     * Output groups, categories and items.
     */
    public function output(): void
    {
        $calculation = $this->calculation;
        $groups = $calculation->getGroups();
        $duplicateItems = $calculation->getDuplicateItems();

        $defaultStyle = PdfStyle::getCellStyle()->setIndent(self::INDENT)->setBorder(PdfBorder::leftRight());
        $errorStyle = (clone $defaultStyle)->setTextColor(PdfTextColor::red());
        $listener = $this->createListener();

        $this->createColumns();
        $this->setGroupListener($listener);
        foreach ($groups as $group) {
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

    /**
     * Render the table for the given calculation.
     */
    public static function render(CalculationReport $parent): self
    {
        $table = new self($parent);
        $table->output();

        return $table;
    }

    public function renderCategory(CalculationCategory $category, PdfStyle $style): true
    {
        $this->checkLines(2);
        $style->setIndent(self::INDENT / 2);
        $this->startRow($style)
            ->add($category->getCode())
            ->completeRow();

        return true;
    }

    public function renderGroup(CalculationGroup $group, PdfStyle $style): true
    {
        $this->checkLines(3);
        $style->resetIndent();
        $this->startRow($style)
            ->add($group->getCode())
            ->completeRow();

        return true;
    }

    public function startRow(?PdfStyle $style = null): static
    {
        if (!$style instanceof PdfStyle) {
            $style = PdfStyle::getCellStyle()
                ->setBorder(PdfBorder::leftRight());
        }
        parent::startRow($style);

        return $this;
    }

    /**
     * Adds description with an error style if duplicate.
     *
     * @param CalculationItem $item           the item to get description for
     * @param array           $duplicateItems the duplicate items
     * @param PdfStyle        $defaultStyle   the style to use if item is not duplicate
     * @param PdfStyle        $errorStyle     the style to use when item is duplicate
     */
    private function addDescription(CalculationItem $item, array $duplicateItems, PdfStyle $defaultStyle, PdfStyle $errorStyle): self
    {
        $style = \in_array($item, $duplicateItems, true) ? $errorStyle : $defaultStyle;
        $this->add($item->getDescription(), style: $style);

        return $this;
    }

    /**
     * Adds format amount with an error style if the amount is equal to 0.
     *
     * @param float     $amount     the amount to output
     * @param ?PdfStyle $errorStyle the error style to use when amount is equal to 0
     */
    private function addStyledAmount(float $amount, ?PdfStyle $errorStyle = null): self
    {
        return $this->addCellAmount($amount, style: 0.0 === $amount ? $errorStyle : null);
    }

    private function checkLines(int $lines): void
    {
        $this->setInProgress(true);
        $this->checkNewPage((float) $lines * PdfDocument::LINE_HEIGHT);
        $this->setInProgress(false);
    }

    private function createColumns(): self
    {
        return $this->addColumns(
            PdfColumn::left($this->trans('calculationitem.fields.description'), 50),
            PdfColumn::left($this->trans('calculationitem.fields.unit'), 20, true),
            PdfColumn::right($this->trans('calculationitem.fields.price'), 20, true),
            PdfColumn::right($this->trans('calculationitem.fields.quantity'), 20, true),
            PdfColumn::right($this->trans('calculationitem.fields.total'), 20, true)
        )->outputHeaders();
    }

    private function createListener(): PdfGroupListenerInterface
    {
        return new class($this, $this->getGroupStyle()) implements PdfGroupListenerInterface {
            public function __construct(private readonly TableItems $parent, private readonly PdfStyle $style)
            {
            }

            public function outputGroup(PdfGroupEvent $event): bool
            {
                /** @psalm-var mixed $key */
                $key = $event->getGroupKey();
                if ($key instanceof CalculationGroup) {
                    return $this->parent->renderGroup($key, $this->style);
                }
                if ($key instanceof CalculationCategory) {
                    return $this->parent->renderCategory($key, $this->style);
                }

                return false;
            }
        };
    }

    /**
     * @psalm-param CalculationItem[] $duplicateItems
     */
    private function renderItem(CalculationItem $item, array $duplicateItems, PdfStyle $defaultStyle, PdfStyle $errorStyle): void
    {
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
            ->addCellTrans('calculation.fields.itemsTotal', cols: 4)
            ->addCellAmount($total)
            ->endRow();
    }
}
