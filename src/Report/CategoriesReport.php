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

use App\Entity\Category;
use App\Pdf\Enums\PdfMove;
use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\PdfBorder;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTableBuilder;
use App\Util\FormatUtils;
use App\Util\Utils;

/**
 * Report for the list of categories.
 *
 * @extends AbstractArrayReport<Category>
 */
class CategoriesReport extends AbstractArrayReport
{
    /**
     * {@inheritdoc}
     *
     * @param Category[] $entities
     */
    protected function doRender(array $entities): bool
    {
        // title
        $this->setTitleTrans('category.list.title', [], true);

        // group by parent code
        $default = $this->trans('report.other');
        /** @var array<string, Category[]> $groups */
        $groups = Utils::groupBy($entities, fn (Category $category) => $category->getGroupCode() ?: $default);

        // new page
        $this->AddPage();

        // table
        $table = $this->createTable();

        // categories by group
        foreach ($groups as $key => $items) {
            $table->setGroupKey($key);
            foreach ($items as $item) {
                $table->startRow()
                    ->add($item->getCode())
                    ->add($item->getDescription())
                    ->add(FormatUtils::formatInt($item->countProducts()))
                    ->add(FormatUtils::formatInt($item->countTasks()))
                    ->endRow();
            }
        }
        $this->resetStyle();

        // count task and products
        $tasks = 0;
        $products = 0;
        foreach ($entities as $item) {
            $tasks += $item->countTasks();
            $products += $item->countProducts();
        }

        // totals
        $txtGroup = $this->trans('counters.groups', ['count' => \count($groups)]);
        $txtCount = $this->trans('counters.categories', ['count' => \count($entities)]);
        $txtProduct = $this->trans('counters.products', ['count' => $products]);
        $txtTask = $this->trans('counters.tasks', ['count' => $tasks]);

        $border = PdfBorder::none();
        $margins = $this->setCellMargin(0);
        $width = $this->GetPageWidth() / 2;
        $this->Cell($width, self::LINE_HEIGHT, $txtGroup . ' - ' . $txtCount, $border, PdfMove::RIGHT);
        $this->Cell(0, self::LINE_HEIGHT, $txtProduct . ' - ' . $txtTask, $border, PdfMove::NEW_LINE, PdfTextAlignment::RIGHT);
        $this->setCellMargin($margins);

        return true;
    }

    /**
     * Creates the table builder.
     */
    private function createTable(): PdfGroupTableBuilder
    {
        $table = new PdfGroupTableBuilder($this);
        $table->addColumn(PdfColumn::left($this->trans('category.fields.code'), 40, true))
            ->addColumn(PdfColumn::left($this->trans('category.fields.description'), 50))
            ->addColumn(PdfColumn::right($this->trans('category.fields.products'), 20, true))
            ->addColumn(PdfColumn::right($this->trans('category.fields.tasks'), 20, true))
            ->outputHeaders();

        return $table;
    }
}
