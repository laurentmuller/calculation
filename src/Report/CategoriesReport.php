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
        $this->renderTotal($groups, $entities);

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

    private function formatCount(string $id, array|int $value): string
    {
        return $this->trans($id, ['count' => \is_array($value) ? \count($value) : $value]);
    }

    /**
     * @psalm-param Category[] $entities
     */
    private function renderTotal(array $groups, array $entities): void
    {
        $tasks = 0;
        $products = 0;
        foreach ($entities as $item) {
            $tasks += $item->countTasks();
            $products += $item->countProducts();
        }

        $txtGroups = $this->formatCount('counters.groups', $groups);
        $Categories = $this->formatCount('counters.categories', $entities);
        $txtProducts = $this->formatCount('counters.products', $products);
        $txtTasks = $this->formatCount('counters.tasks', $tasks);

        $border = PdfBorder::none();
        $margins = $this->setCellMargin(0);
        $width = $this->GetPageWidth() / 2.0;
        $this->Cell(
            w: $width,
            txt: \sprintf('%s - %s', $txtGroups, $Categories),
            border: $border
        );
        $this->Cell(
            txt: \sprintf('%s - %s', $txtProducts, $txtTasks),
            border: $border,
            align: PdfTextAlignment::RIGHT
        );
        $this->setCellMargin($margins);
    }
}
