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
use App\Entity\CalculationCategory;
use App\Entity\CalculationGroup;
use App\Entity\CalculationItem;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTextColor;
use App\Traits\TranslatorTrait;
use App\Util\FormatUtils;

/**
 * Table to render the groups, categories and items of a calculation.
 *
 * @author Laurent Muller
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
    private Calculation $calculation;

    /**
     * Constructor.
     */
    public function __construct(CalculationReport $parent)
    {
        parent::__construct($parent);
        $this->calculation = $parent->getCalculation();
        $this->translator = $parent->getTranslator();
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
        $groupStyle = $this->getGroup()->getStyle();
        $defaultStyle = PdfStyle::getCellStyle()->setIndent(self::INDENT);
        $errorStyle = (clone $defaultStyle)->setTextColor(PdfTextColor::red());

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

        foreach ($groups as $group) {
            $this->checkLines(3);
            $groupStyle->resetIndent();
            $this->setGroupKey($group->getCode());

            /** @var CalculationCategory $category */
            foreach ($group->getCategories() as $category) {
                $this->checkLines(2);
                $groupStyle->setIndent(self::INDENT);
                $this->setGroupKey($category->getCode());

                /** @var CalculationItem $item */
                foreach ($category->getItems() as $item) {
                    // @phpstan-ignore-next-line
                    $this->startRow()
                        ->addDescription($item, $duplicateItems, $defaultStyle, $errorStyle)
                        ->add($item->getUnit())
                        ->addAmount($item->getPrice(), $errorStyle)
                        ->addAmount($item->getQuantity(), $errorStyle)
                        ->addAmount($item->getTotal(), null)
                        ->endRow();
                }
            }
        }

        // total
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
     * @param float    $amount     the amount to output
     * @param PdfStyle $errorStyle the error style to use when amount is equal to 0
     */
    protected function addAmount(float $amount, ?PdfStyle $errorStyle): self
    {
        $text = FormatUtils::formatAmount($amount);
        $style = empty($amount) ? $errorStyle : null;
        $this->add($text, 1, $style);

        return $this;
    }

    /**
     * Adds descriptoin with an error style if duplicate.
     *
     * @param CalculationItem $item           the item to get description for
     * @param array           $duplicateItems the duplicate items
     * @param PdfStyle        $defaultStyle   the style to use if item is not duplicate
     * @param PdfStyle        $errorStyle     the style to use when item is duplicate
     */
    protected function addDescription(CalculationItem $item, array $duplicateItems, PdfStyle $defaultStyle, PdfStyle $errorStyle): self
    {
        $style = \in_array($item, $duplicateItems, true) ? $errorStyle : $defaultStyle;
        $this->add($item->getDescription(), 1, $style);

        return $this;
    }

    /**
     * Check if the given number of lines can be outputed.
     */
    private function checkLines(int $lines): bool
    {
        $this->inProgress = true;
        $this->repeatHeader = false;
        $result = $this->checkNewPage($lines * self::LINE_HEIGHT);
        $this->repeatHeader = false;
        $this->inProgress = false;

        return $result;
    }
}
