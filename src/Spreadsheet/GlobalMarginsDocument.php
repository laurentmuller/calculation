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

use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Spreadsheet document for the list of global margins.
 *
 * @author Laurent Muller
 *
 * @extends AbstractArrayDocument<\App\Entity\GlobalMargin>
 */
class GlobalMarginsDocument extends AbstractArrayDocument
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
