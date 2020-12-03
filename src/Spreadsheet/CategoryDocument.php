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

use App\Entity\Category;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Excel document for the list of categories.
 *
 * @author Laurent Muller
 */
class CategoryDocument extends AbstractArrayDocument
{
    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        // initialize
        $this->start('category.list.title');

        // headers
        $this->setHeaderValues([
            'category.fields.code' => Alignment::HORIZONTAL_GENERAL,
            'category.fields.description' => Alignment::HORIZONTAL_GENERAL,
            'category.fields.parent' => Alignment::HORIZONTAL_GENERAL,
            'category.fields.products' => Alignment::HORIZONTAL_RIGHT,
        ]);

        // formats
        $this->setFormatInt(4);

        // rows
        $row = 2;
        /** @var Category $entity */
        foreach ($entities as $entity) {
            $this->setRowValues($row++, [
                $entity->getCode(),
                $entity->getDescription(),
                $entity->getParentCode(),
                $entity->countProducts(),
            ]);
        }

        $this->finish();

        return true;
    }
}
