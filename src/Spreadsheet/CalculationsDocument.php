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

use App\Entity\Calculation;
use App\Traits\CalculationDocumentMarginTrait;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Spreadsheet document for the list of calculations.
 *
 * @extends AbstractArrayDocument<Calculation>
 */
class CalculationsDocument extends AbstractArrayDocument
{
    use CalculationDocumentMarginTrait;

    /**
     * {@inheritdoc}
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function doRender(array $entities): bool
    {
        $title = $this->getTitle() ?? 'calculation.list.title';
        $this->start($title, true);
        $row = $this->setHeaderValues([
            'calculation.fields.id' => Alignment::HORIZONTAL_CENTER,
            'calculation.fields.date' => Alignment::HORIZONTAL_CENTER,
            'calculation.fields.state' => Alignment::HORIZONTAL_GENERAL,
            'calculation.fields.customer' => Alignment::HORIZONTAL_GENERAL,
            'calculation.fields.description' => Alignment::HORIZONTAL_GENERAL,
            'calculationgroup.fields.amount' => Alignment::HORIZONTAL_RIGHT,
            'calculation.fields.margin' => Alignment::HORIZONTAL_RIGHT,
            'calculation.fields.total' => Alignment::HORIZONTAL_RIGHT,
        ]);
        $this->setFormatId(1)
            ->setFormatDate(2)
            ->setFormatAmount(6)
            ->setFormat(7, $this->getMarginFormat())
            ->setFormatAmount(8);
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
}
