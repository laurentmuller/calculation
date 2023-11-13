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
use App\Entity\CalculationItem;
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\PdfColumn;
use App\Pdf\PdfDocument;
use App\Pdf\PdfGroupTable;
use App\Pdf\PdfStyle;
use App\Report\CalculationReport;
use App\Traits\TranslatorTrait;
use App\Utils\FormatUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Table to render the groups, categories and items of a calculation.
 */
class TableItems extends PdfGroupTable
{
    use TranslatorTrait;
    /**
     * The categories and items indent.
     */
    private const INDENT = 4;

    private readonly Calculation $calculation;
    private readonly TranslatorInterface $translator;

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
     * Output groups, categories and items.
     */
    public function output(): void
    {
        $calculation = $this->calculation;
        $groups = $calculation->getGroups();
        $duplicateItems = $calculation->getDuplicateItems();

        $groupStyle = $this->findGroupStyle();
        $defaultStyle = PdfStyle::getCellStyle()->setIndent(self::INDENT);
        $errorStyle = (clone $defaultStyle)->setTextColor(PdfTextColor::red());

        $this->createColumns();
        foreach ($groups as $group) {
            $this->checkLines(3);
            $groupStyle->resetIndent();
            $this->setGroupKey($group->getCode());
            foreach ($group->getCategories() as $category) {
                $this->outputCategory($category, $groupStyle);
                foreach ($category->getItems() as $item) {
                    $this->outputItem($item, $duplicateItems, $defaultStyle, $errorStyle);
                }
            }
        }
        $this->setInProgress(true);
        $this->outputTotal($calculation->getItemsTotal());
        $this->getParent()->Ln(3);
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

    /**
     * Adds formatted amount with an error style if the amount is equal to 0.
     *
     * @param float     $amount     the amount to output
     * @param ?PdfStyle $errorStyle the error style to use when amount is equal to 0
     */
    private function addAmount(float $amount, PdfStyle $errorStyle = null): self
    {
        return $this->add(text: FormatUtils::formatAmount($amount), style: 0.0 === $amount ? $errorStyle : null);
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
        $this->add(text: $item->getDescription(), style: $style);

        return $this;
    }

    private function checkLines(int $lines): bool
    {
        $this->setInProgress(true);
        $result = $this->checkNewPage((float) $lines * PdfDocument::LINE_HEIGHT);
        $this->setInProgress(false);

        return $result;
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

    private function findGroupStyle(): PdfStyle
    {
        $style = $this->getGroupStyle();
        if ($style instanceof PdfStyle) {
            return $style;
        }

        return PdfStyle::getCellStyle()->setFontBold();
    }

    private function outputCategory(CalculationCategory $category, PdfStyle $groupStyle): void
    {
        $this->checkLines(2);
        $groupStyle->setIndent(self::INDENT / 2);
        $this->setGroupKey($category->getCode());
    }

    /**
     * @psalm-param CalculationItem[] $duplicateItems
     */
    private function outputItem(CalculationItem $item, array $duplicateItems, PdfStyle $defaultStyle, PdfStyle $errorStyle): void
    {
        $this->startRow()
            ->addDescription($item, $duplicateItems, $defaultStyle, $errorStyle)
            ->add($item->getUnit())
            ->addAmount($item->getPrice(), $errorStyle)
            ->addAmount($item->getQuantity(), $errorStyle)
            ->addAmount($item->getTotal())
            ->endRow();
    }

    private function outputTotal(float $total): void
    {
        $this->startHeaderRow()
            ->add($this->trans('calculation.fields.itemsTotal'), 4)
            ->addAmount($total)
            ->endRow();
    }
}
