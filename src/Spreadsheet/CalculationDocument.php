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

use App\Entity\Calculation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Excel document for the list of calculations.
 *
 * @author Laurent Muller
 */
class CalculationDocument extends AbstractArrayDocument
{
    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        // initialize
        $this->start('calculation.list.title', true);

        // headers
        $this->setHeaderValues([
            'calculation.fields.id' => Alignment::HORIZONTAL_CENTER,
            'calculation.fields.date' => Alignment::HORIZONTAL_CENTER,
            'calculation.fields.state' => Alignment::HORIZONTAL_GENERAL,
            'calculation.fields.customer' => Alignment::HORIZONTAL_GENERAL,
            'calculation.fields.description' => Alignment::HORIZONTAL_GENERAL,
            'calculationgroup.fields.amount' => Alignment::HORIZONTAL_RIGHT,
            'calculation.fields.margin' => Alignment::HORIZONTAL_RIGHT,
            'calculation.fields.total' => Alignment::HORIZONTAL_RIGHT,
        ]);

        // formats
        $minMargin = $this->getMinMargin();
        $percentage = $this->getPercentFormat();
        $format = "[Red][<$minMargin]$percentage;$percentage";
        $this->setFormatId(1)
            ->setFormatDate(2)
            ->setFormatAmount(6)
            ->setFormat(7, $format)
            ->setFormatAmount(8);

        // rows
        $row = 2;
        /** @var Calculation $entity */
        foreach ($entities as $entity) {
            $this->setRowValues($row++, [
                $entity->getId(),
                $entity->getDate(),
                $entity->getStateCode(),
                $entity->getCustomer(),
                $entity->getDescription(),
                $entity->getItemsTotal(),
                $entity->getOverallMargin(),
                $entity->getOverallTotal(),
            ]);
        }

        $this->finish();

        return true;
    }

    /**
     * Gets the minimum margin.
     */
    private function getMinMargin(): float
    {
        return $this->controller->getApplication()->getMinMargin();
    }
}
