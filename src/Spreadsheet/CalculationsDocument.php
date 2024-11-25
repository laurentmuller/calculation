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

use App\Traits\CalculationDocumentMarginTrait;

/**
 * Spreadsheet document for the list of calculations.
 *
 * @extends AbstractArrayDocument<\App\Entity\Calculation>
 */
class CalculationsDocument extends AbstractArrayDocument
{
    use CalculationDocumentMarginTrait;

    /**
     * @param \App\Entity\Calculation[] $entities
     */
    protected function doRender(array $entities): bool
    {
        $title = $this->getTitle() ?? 'calculation.list.title';
        $this->start($title, true);

        $sheet = $this->getActiveSheet();
        $row = $sheet->setHeaders([
            'calculation.fields.id' => HeaderFormat::id(),
            'calculation.fields.date' => HeaderFormat::date(),
            'calculation.fields.state' => HeaderFormat::instance(),
            'calculation.fields.customer' => HeaderFormat::instance(),
            'calculation.fields.description' => HeaderFormat::instance(),
            'calculationgroup.fields.amount' => HeaderFormat::amount(),
            'calculation.fields.margin' => HeaderFormat::percentCustom($this->getMarginFormat()),
            'calculation.fields.total' => HeaderFormat::amount(),
        ]);

        foreach ($entities as $entity) {
            $sheet->setRowValues($row++, [
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
        $sheet->finish();

        return true;
    }
}
