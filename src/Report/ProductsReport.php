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

use App\Entity\Product;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTableBuilder;
use App\Util\FormatUtils;

/**
 * Report for the list of products.
 *
 * @author Laurent Muller
 */
class ProductsReport extends AbstractArrayReport
{
    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        //title
        $this->setTitleTrans('product.list.title');

        // new page
        $this->AddPage();

        // create table
        $table = $this->createTable();

        /** @var Product $entity */
        foreach ($entities as $entity) {
            // group
            $key = \sprintf('%s - %s', $entity->getGroupCode(), $entity->getCategoryCode());
            $table->setGroupKey($key);

            // product
            $table->startRow()
                ->add($entity->getDescription())
                ->add($entity->getSupplier())
                ->add($entity->getUnit())
                ->add(FormatUtils::formatAmount($entity->getPrice()))
                ->endRow();
        }

        // count
        return $this->renderCount(\count($entities));
    }

    /**
     * Creates the table.
     */
    private function createTable(): PdfGroupTableBuilder
    {
        $columns = [
            PdfColumn::left($this->trans('product.fields.description'), 90),
            PdfColumn::left($this->trans('product.fields.supplier'), 45, true),
            PdfColumn::left($this->trans('product.fields.unit'), 20, true),
            PdfColumn::right($this->trans('product.fields.price'), 20, true),
        ];

        $table = new PdfGroupTableBuilder($this);
        $table->addColumns($columns)->outputHeaders();

        return $table;
    }
}
