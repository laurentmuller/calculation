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
use App\Traits\GroupByTrait;
use App\Utils\FormatUtils;

/**
 * Report for the list of categories.
 *
 * @extends AbstractArrayReport<Category>
 */
class CategoriesReport extends AbstractArrayReport
{
    use GroupByTrait;

    /**
     * {@inheritdoc}
     *
     * @param Category[] $entities
     */
    protected function doRender(array $entities): bool
    {
        $this->setTitleTrans('category.list.title', [], true);
        $default = $this->trans('report.other');
        $fn = fn (Category $category): string => $category->getGroupCode() ?? $default;
        /** @var array<string, Category[]> $groups */
        $groups = $this->groupBy($entities, $fn);
        $this->AddPage();
        $table = $this->createTable();
        foreach ($groups as $key => $items) {
            $table->setGroupKey($key);
            foreach ($items as $item) {
                $table->addRow(
                    $item->getCode(),
                    $item->getDescription(),
                    FormatUtils::formatInt($item->countProducts()),
                    FormatUtils::formatInt($item->countTasks())
                );
            }
        }
        $this->resetStyle();
        $tasks = 0;
        $products = 0;
        foreach ($entities as $item) {
            $tasks += $item->countTasks();
            $products += $item->countProducts();
        }
        $txtGroup = $this->trans('counters.groups', ['count' => \count($groups)]);
        $txtCount = $this->trans('counters.categories', ['count' => \count($entities)]);
        $txtProduct = $this->trans('counters.products', ['count' => $products]);
        $txtTask = $this->trans('counters.tasks', ['count' => $tasks]);
        $border = PdfBorder::none();
        $margins = $this->setCellMargin(0);
        $width = $this->GetPageWidth() / 2.0;
        $this->Cell(
            w: $width,
            txt: $txtGroup . ' - ' . $txtCount,
            border: $border
        );
        $this->Cell(
            txt: $txtProduct . ' - ' . $txtTask,
            border: $border,
            ln: PdfMove::NEW_LINE,
            align: PdfTextAlignment::RIGHT
        );
        $this->setCellMargin($margins);

        return true;
    }

    /**
     * Creates the table builder.
     */
    private function createTable(): PdfGroupTableBuilder
    {
        return PdfGroupTableBuilder::instance($this)
            ->addColumns(
                PdfColumn::left($this->trans('category.fields.code'), 40, true),
                PdfColumn::left($this->trans('category.fields.description'), 50),
                PdfColumn::right($this->trans('category.fields.products'), 20, true),
                PdfColumn::right($this->trans('category.fields.tasks'), 20, true)
            )->outputHeaders();
    }
}
