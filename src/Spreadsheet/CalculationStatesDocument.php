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

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Spreadsheet document for the list of calculation state.
 *
 * @author Laurent Muller
 *
 * @extends AbstractArrayDocument<\App\Entity\CalculationState>
 */
class CalculationStatesDocument extends AbstractArrayDocument
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
