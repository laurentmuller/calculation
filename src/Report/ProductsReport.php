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

use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTextColor;
use App\Util\FormatUtils;

/**
 * Report for the list of products.
 *
 * @extends AbstractArrayReport<\App\Entity\Product>
 */
class ProductsReport extends AbstractArrayReport
{
    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        // title
        $this->setTitleTrans('product.list.title');

        // new page
        $this->AddPage();

        // create table
        $table = $this->createTable();

        // style for empty price
        $emptyStyle = PdfStyle::getCellStyle()
            ->setTextColor(PdfTextColor::red());

        foreach ($entities as $entity) {
            // group
            $key = \sprintf('%s / %s', (string) $entity->getGroupCode(), (string) $entity->getCategoryCode());
            $table->setGroupKey($key);

            $style = empty($entity->getPrice()) ? $emptyStyle : null;

            // product
            $table->startRow()
                ->add($entity->getDescription())
                ->add(text: FormatUtils::formatAmount($entity->getPrice()), style: $style)
                ->add($entity->getUnit())
                ->add($entity->getSupplier())
                ->endRow();
        }

        // count
        return $this->renderCount($entities);
    }

    /**
     * Creates the table.
     */
    private function createTable(): PdfGroupTableBuilder
    {
        $columns = [
            PdfColumn::left($this->trans('product.fields.description'), 90),
            PdfColumn::right($this->trans('product.fields.price'), 20, true),
            PdfColumn::left($this->trans('product.fields.unit'), 20, true),
            PdfColumn::left($this->trans('product.fields.supplier'), 45, true),
        ];

        $table = new PdfGroupTableBuilder($this);
        $table->addColumns($columns)->outputHeaders();

        return $table;
    }
}
