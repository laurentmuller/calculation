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
use App\Entity\CalculationItem;
use App\Pdf\PdfColumn;
use App\Pdf\PdfDocument;
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTextColor;
use App\Traits\TranslatorTrait;
use App\Util\FormatUtils;

/**
 * Table to render the groups, categories and items of a calculation.
 */
class CalculationTableItems extends PdfGroupTableBuilder
{
    use TranslatorTrait;

    /**
     * The categories and items indent.
     */
    private const INDENT = 4;

    /**
     * The calculation to render.
     */
    private readonly Calculation $calculation;

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
     * Output groups, categories and items.
     */
    public function output(): void
    {
        $calculation = $this->calculation;

        /** @var CalculationGroup[] $groups */
        $groups = $calculation->getGroups();
        $duplicateItems = $calculation->getDuplicateItems();

        // styles
        $groupStyle = $this->findGroupStyle();
        $defaultStyle = PdfStyle::getCellStyle()->setIndent(self::INDENT);
        $errorStyle = (clone $defaultStyle)->setTextColor(PdfTextColor::red());

        // headers
        $this->addColumns(
            PdfColumn::left($this->trans('calculationitem.fields.description'), 50),
            PdfColumn::left($this->trans('calculationitem.fields.unit'), 20, true),
            PdfColumn::right($this->trans('calculationitem.fields.price'), 20, true),
            PdfColumn::right($this->trans('calculationitem.fields.quantity'), 20, true),
            PdfColumn::right($this->trans('calculationitem.fields.total'), 20, true)
        )->outputHeaders();

        foreach ($groups as $group) {
            $this->checkLines(3);
            $groupStyle->resetIndent();
            $this->setGroupKey($group->getCode());

            foreach ($group->getCategories() as $category) {
                $this->checkLines(2);
                $groupStyle->setIndent(self::INDENT / 2);
                $this->setGroupKey($category->getCode());

                foreach ($category->getItems() as $item) {
                    $this->startRow();
                    $this->addDescription($item, $duplicateItems, $defaultStyle, $errorStyle);
                    $this->add($item->getUnit());
                    $this->addAmount($item->getPrice(), $errorStyle)
                        ->addAmount($item->getQuantity(), $errorStyle)
                        ->addAmount($item->getTotal())
                        ->endRow();
                }
            }
        }

        // total
        $this->inProgress = true;
        $total = $calculation->getItemsTotal();
        $this->startHeaderRow()
            ->add($this->trans('calculation.fields.itemsTotal'), 4)
            ->add(FormatUtils::formatAmount($total))
            ->endRow();
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
    protected function addAmount(float $amount, ?PdfStyle $errorStyle = null): self
    {
        $text = FormatUtils::formatAmount($amount);
        $style = empty($amount) ? $errorStyle : null;
        $this->add(text: $text, style: $style);

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
    protected function addDescription(CalculationItem $item, array $duplicateItems, PdfStyle $defaultStyle, PdfStyle $errorStyle): self
    {
        $style = \in_array($item, $duplicateItems, true) ? $errorStyle : $defaultStyle;
        $this->add(text: $item->getDescription(), style: $style);

        return $this;
    }

    private function checkLines(int $lines): bool
    {
        $this->inProgress = true;
        $result = $this->checkNewPage($lines * PdfDocument::LINE_HEIGHT);
        $this->inProgress = false;

        return $result;
    }

    private function findGroupStyle(): PdfStyle
    {
        if (null !== $style = $this->getGroupStyle()) {
            return $style;
        }

        return PdfStyle::getCellStyle()->setFontBold();
    }
}
