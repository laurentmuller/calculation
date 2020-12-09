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
