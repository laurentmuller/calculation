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
use App\Pdf\PdfGroupTable;
use App\Pdf\PdfStyle;
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

    #[\Override]
    protected function doRender(array $entities): bool
    {
        $this->setTranslatedTitle(id: 'category.list.title', isUTF8: true);

        $this->addPage();
        $table = $this->createTable();
        /** @var array<string, Category[]> $groups */
        $groups = $this->groupEntities($entities);
        $style = PdfStyle::getCellStyle()->setIndent(2);

        foreach ($groups as $key => $items) {
            $table->setGroupKey($key);
            foreach ($items as $item) {
                $table->startRow()
                    ->add($item->getCode(), 1, $style)
                    ->add($item->getDescription())
                    ->add($this->formatInt($item->countProducts()))
                    ->add($this->formatInt($item->countTasks()))
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

    private function createTable(): PdfGroupTable
    {
        return PdfGroupTable::instance($this)
            ->addColumns(
                $this->leftColumn('category.fields.code', 45, true),
                $this->leftColumn('category.fields.description', 50),
                $this->rightColumn('category.fields.products', 26, true),
                $this->rightColumn('category.fields.tasks', 26, true)
            )->outputHeaders();
    }

    private function formatCount(string $id, array|int $value): string
    {
        return $this->trans($id, ['count' => \is_array($value) ? \count($value) : $value]);
    }

    private function formatInt(int $value): string
    {
        return 0 === $value ? '' : FormatUtils::formatInt($value);
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
    private function renderTotal(PdfGroupTable $table, array $entities): void
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
