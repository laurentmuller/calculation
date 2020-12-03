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

use App\Entity\CalculationState;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Excel document for the list of calculation state.
 *
 * @author Laurent Muller
 */
class CalculationStateDocument extends AbstractArrayDocument
{
    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        // initialize
        $this->start('calculationstate.list.title', true);

        // headers
        $this->setHeaderValues([
            'calculationstate.fields.code' => Alignment::HORIZONTAL_GENERAL,
            'calculationstate.fields.description' => Alignment::HORIZONTAL_GENERAL,
            'calculationstate.fields.editable' => Alignment::HORIZONTAL_RIGHT,
            'calculationstate.fields.calculations' => Alignment::HORIZONTAL_RIGHT,
            'calculationstate.fields.color' => Alignment::HORIZONTAL_CENTER,
        ]);

        // formats
        $this->setFormatYesNo(3)
            ->setFormatInt(4);

        // rows
        $row = 2;
        /** @var CalculationState $entity */
        foreach ($entities as $entity) {
            $this->setRowValues($row, [
                $entity->getCode(),
                $entity->getDescription(),
                $entity->isEditable(),
                $entity->countCalculations(),
            ]);

            // color
            $col = $this->stringFromColumnIndex(5);
            $color = new Color(\substr($entity->getColor(), 1));
            $fill = $this->getActiveSheet()
                ->getStyle("$col$row")
                ->getFill();
            $fill->setFillType(Fill::FILL_SOLID)
                ->setStartColor($color);

            ++$row;
        }

        $this->finish();

        return true;
    }
}
