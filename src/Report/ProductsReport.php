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

use App\Pdf\Colors\PdfTextColor;
use App\Pdf\PdfStyle;
use App\Report\Table\ReportGroupTable;

/**
 * Report for the list of products.
 *
 * @extends AbstractArrayReport<\App\Entity\Product>
 */
class ProductsReport extends AbstractArrayReport
{
    #[\Override]
    protected function doRender(array $entities): bool
    {
        $this->setTranslatedTitle('product.list.title');
        $this->addPage();
        $table = $this->createTable();
        $redStyle = PdfStyle::getCellStyle()
            ->setTextColor(PdfTextColor::red());

        $key = '';
        $group = '';
        $category = '';
        foreach ($entities as $entity) {
            $newGroup = $entity->getGroupCode();
            if ($group !== $newGroup) {
                $group = $newGroup;
                $this->addBookmark($group, true);
            }
            $newCategory = $entity->getCategoryCode();
            if ($category !== $newCategory) {
                $category = $newCategory;
                $this->addBookmark($category, true, 1);
            }
            $newKey = \sprintf('%s - %s', $group, $category);
            if ($key !== $newKey) {
                $key = $newKey;
                $table->setGroupKey($key);
            }

            $style = $this->isFloatZero($entity->getPrice()) ? $redStyle : null;
            $table->startRow()
                ->add($entity->getDescription())
                ->addCellAmount($entity->getPrice(), style: $style)
                ->add($entity->getUnit())
                ->add($entity->getSupplier())
                ->endRow();
        }
        $this->renderCount($table, $entities, 'counters.products');
        $this->addPageIndex();

        return true;
    }

    /**
     * Creates the table.
     */
    private function createTable(): ReportGroupTable
    {
        return ReportGroupTable::fromReport($this)
            ->addColumns(
                $this->leftColumn('product.fields.description', 90),
                $this->rightColumn('product.fields.price', 20, true),
                $this->leftColumn('product.fields.unit', 20, true),
                $this->leftColumn('product.fields.supplier', 40, true)
            )->outputHeaders();
    }
}
