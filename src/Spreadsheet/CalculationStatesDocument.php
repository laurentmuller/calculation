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

use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Spreadsheet document for the list of calculation state.
 *
 * @extends AbstractArrayDocument<\App\Entity\CalculationState>
 */
class CalculationStatesDocument extends AbstractArrayDocument
{
    /**
     * @param \App\Entity\CalculationState[] $entities
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function doRender(array $entities): bool
    {
        $this->start('calculationstate.list.title', true);

        $row = $this->setHeaders([
            'calculationstate.fields.code' => HeaderFormat::instance(),
            'calculationstate.fields.description' => HeaderFormat::instance(),
            'calculationstate.fields.editable' => HeaderFormat::yesNo(),
            'calculationstate.fields.calculations' => HeaderFormat::int(),
            'calculationstate.fields.color' => HeaderFormat::center(),
        ]);

        foreach ($entities as $entity) {
            $this->setRowValues($row, [
                $entity->getCode(),
                $entity->getDescription(),
                $entity->isEditable(),
                $entity->countCalculations(),
            ]);
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
