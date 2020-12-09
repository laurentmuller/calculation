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

use App\Entity\Category;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Util\FormatUtils;
use App\Util\Utils;

/**
 * Report for the list of categories.
 *
 * @author Laurent Muller
 */
class CategoriesReport extends AbstractArrayReport
{
    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        // title
        $this->setTitleTrans('category.list.title', [], true);

        // group by parent code
        $default = $this->trans('report.other');
        $groups = Utils::groupBy($entities, function (Category $category) use ($default) {
            return $category->getGroupCode() ?: $default;
        });

        // count values
        $productsCount = \array_reduce($entities, function (int $carry, Category $category) {
            return $carry + $category->countProducts();
        }, 0);

        // new page
        $this->AddPage();

        // table
        $table = $this->createTable();

        // categories by group
        $style = PdfStyle::getCellStyle()->setIndent(2);
        foreach ($groups as $key => $items) {
            $table->setGroupKey($key);
            /** @var Category $item */
            foreach ($items as $item) {
                $table->startRow()
                    ->add($item->getCode(), 1, $style)
                    ->add($item->getDescription())
                    ->add(FormatUtils::formatInt($item->countProducts()))
                    ->endRow();
            }
        }
        $this->resetStyle();

        // totals
        $txtGroup = $this->trans('counters.groups', [
            'count' => \count($groups),
        ]);
        $txtCount = $this->trans('counters.categories', [
            'count' => \count($entities),
        ]);
        $txtProduct = $this->trans('counters.products', [
            'count' => $productsCount,
        ]);

        $table = new PdfTableBuilder($this);
        $table->addColumn(PdfColumn::left(null, 20))
            ->addColumn(PdfColumn::center(null, 20))
            ->addColumn(PdfColumn::right(null, 20))
            ->startRow(PdfStyle::getNoBorderStyle())
            ->add($txtGroup)
            ->add($txtCount)
            ->add($txtProduct)
            ->endRow();

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
            ->addColumn(PdfColumn::right($this->trans('category.fields.products'), 15, true))
            ->outputHeaders();

        return $table;
    }
}
