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
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
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

    protected function doRender(array $entities): bool
    {
        $this->setTitleTrans('category.list.title', [], true);

        $this->AddPage();
        $table = $this->createTable();
        /** @psalm-var array<string, Category[]> $groups */
        $groups = $this->groupEntities($entities);
        $style = PdfStyle::getCellStyle()->setIndent(2);

        foreach ($groups as $key => $items) {
            $table->setGroupKey($key);
            foreach ($items as $item) {
                $table->startRow()
                    ->add($item->getCode(), 1, $style)
                    ->add($item->getDescription())
                    ->add(FormatUtils::formatInt($item->countProducts()))
                    ->add(FormatUtils::formatInt($item->countTasks()))
                    ->endRow();
            }
        }
        $this->renderTotal($table, $entities);

        return true;
    }

    /**
     * @param Category[] $entities
     */
    private function countProducts(array $entities): int
    {
        return \array_reduce($entities, static fn (int $carry, Category $c): int => $carry + $c->countProducts(), 0);
    }

    /**
     * @param Category[] $entities
     */
    private function countTasks(array $entities): int
    {
        return \array_reduce($entities, static fn (int $carry, Category $c): int => $carry + $c->countTasks(), 0);
    }

    private function createTable(): PdfGroupTableBuilder
    {
        return PdfGroupTableBuilder::instance($this)
            ->addColumns(
                PdfColumn::left($this->trans('category.fields.code'), 45, true),
                PdfColumn::left($this->trans('category.fields.description'), 50),
                PdfColumn::right($this->trans('category.fields.products'), 26, true),
                PdfColumn::right($this->trans('category.fields.tasks'), 26, true)
            )->outputHeaders();
    }

    private function formatCount(string $id, array|int $value): string
    {
        return $this->trans($id, ['count' => \is_array($value) ? \count($value) : $value]);
    }

    /**
     * @param Category[] $entities
     */
    private function groupEntities(array $entities): array
    {
        $default = $this->trans('report.other');
        $callback = fn (Category $category): string => $category->getGroupCode() ?? $default;

        return $this->groupBy($entities, $callback);
    }

    /**
     * @param Category[] $entities
     */
    private function renderTotal(PdfTableBuilder $table, array $entities): void
    {
        $categories = $this->formatCount('counters.categories', $entities);
        $products = $this->formatCount('counters.products', $this->countProducts($entities));
        $tasks = $this->formatCount('counters.tasks', $this->countTasks($entities));

        $table->startHeaderRow()
            ->add($categories, 2)
            ->add($products)
            ->add($tasks)
            ->endRow();
    }
}
