<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Spreadsheet;

use App\Entity\Product;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Excel document for the list of products.
 *
 * @author Laurent Muller
 */
class ProductDocument extends AbstractArrayDocument
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

        // formats
        $this->setFormatAmount(3);

        // rows
        $row = 2;
        /** @var Product $entity */
        foreach ($entities as $entity) {
            $this->setRowValues($row++, [
                $entity->getParentCode(),
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
