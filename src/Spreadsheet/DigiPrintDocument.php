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

use App\Entity\DigiPrint;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Excel document for the list of DigipPints.
 *
 * @author Laurent Muller
 */
class DigiPrintDocument extends AbstractArrayDocument
{
    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        // initialize
        $this->start('digiprint.list.title');

        // headers
        $this->setHeaderValues([
            'digiprint.fields.format' => Alignment::HORIZONTAL_GENERAL,
            'digiprint.fields.width' => Alignment::HORIZONTAL_RIGHT,
            'digiprint.fields.height' => Alignment::HORIZONTAL_RIGHT,
            'digiprint.fields.price' => Alignment::HORIZONTAL_RIGHT,
            'digiprint.fields.blacklit' => Alignment::HORIZONTAL_RIGHT,
            'digiprint.fields.replicating' => Alignment::HORIZONTAL_RIGHT,
        ]);

        // rows
        $row = 2;
        /** @var DigiPrint $entity */
        foreach ($entities as $entity) {
            $this->setRowValues($row++, [
                $entity->getFormat(),
                $entity->getWidth(),
                $entity->getHeight(),
                $entity->getItemsPrice()->count(),
                $entity->getItemsBacklit()->count(),
                $entity->getItemsReplicating()->count(),
            ]);
        }

        $this->finish();

        return true;
    }
}
