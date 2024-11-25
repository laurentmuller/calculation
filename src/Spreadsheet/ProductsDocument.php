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

namespace App\Spreadsheet;

/**
 * Spreadsheet document for the list of products.
 *
 * @extends AbstractArrayDocument<\App\Entity\Product>
 */
class ProductsDocument extends AbstractArrayDocument
{
    /**
     * @param \App\Entity\Product[] $entities
     */
    protected function doRender(array $entities): bool
    {
        $this->start('product.list.title');

        $sheet = $this->getActiveSheet();
        $row = $sheet->setHeaders([
            'product.fields.description' => HeaderFormat::instance(),
            'product.fields.group' => HeaderFormat::instance(),
            'product.fields.category' => HeaderFormat::instance(),
            'product.fields.price' => HeaderFormat::amountZero(),
            'product.fields.unit' => HeaderFormat::instance(),
            'product.fields.supplier' => HeaderFormat::instance(),
        ]);

        foreach ($entities as $entity) {
            $sheet->setRowValues($row++, [
                $entity->getDescription(),
                $entity->getGroupCode(),
                $entity->getCategoryCode(),
                $entity->getPrice(),
                $entity->getUnit(),
                $entity->getSupplier(),
            ]);
        }
        $sheet->finish();

        return true;
    }
}
