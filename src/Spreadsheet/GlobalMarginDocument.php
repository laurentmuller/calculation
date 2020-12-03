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

use App\Entity\GlobalMargin;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Excel document for the list of global margins.
 *
 * @author Laurent Muller
 */
class GlobalMarginDocument extends AbstractArrayDocument
{
    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        // initialize
        $this->start('globalmargin.list.title');

        // headers
        $this->setHeaderValues([
            'globalmargin.fields.minimum' => Alignment::HORIZONTAL_RIGHT,
            'globalmargin.fields.maximum' => Alignment::HORIZONTAL_RIGHT,
            'globalmargin.fields.margin' => Alignment::HORIZONTAL_RIGHT,
        ]);

        // formats
        $this->setFormatAmount(1)
            ->setFormatAmount(2)
            ->setFormatPercent(3);

        // rows
        $row = 2;
        /** @var GlobalMargin $entity */
        foreach ($entities as $entity) {
            $this->setRowValues($row++, [
                    $entity->getMinimum(),
                    $entity->getMaximum(),
                    $entity->getMargin(),
                ]);
        }

        $this->finish();

        return true;
    }
}
