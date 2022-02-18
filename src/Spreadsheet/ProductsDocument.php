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

namespace App\Spreadsheet;

use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Spreadsheet document for the list of products.
 *
 * @author Laurent Muller
 *
 * @extends AbstractArrayDocument<\App\Entity\Product>
 */
class ProductsDocument extends AbstractArrayDocument
{
    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        // initialize
        $this->start('product.list.title');

        // headers
        $this->setHeaderValues([
            'product.fields.group' => Alignment::HORIZONTAL_GENERAL,
            'product.fields.category' => Alignment::HORIZONTAL_GENERAL,
            'product.fields.description' => Alignment::HORIZONTAL_GENERAL,
            'product.fields.price' => Alignment::HORIZONTAL_RIGHT,
            'product.fields.unit' => Alignment::HORIZONTAL_GENERAL,
            'product.fields.supplier' => Alignment::HORIZONTAL_GENERAL,
        ]);

        // price format
        $this->setFormatPrice(4);

        // rows
        $row = 2;
        foreach ($entities as $entity) {
            $this->setRowValues($row++, [
                $entity->getGroupCode(),
                $entity->getCategoryCode(),
                $entity->getDescription(),
                $entity->getPrice(),
                $entity->getUnit(),
                $entity->getSupplier(),
            ]);
        }

        $this->finish();

        return true;
    }
}
