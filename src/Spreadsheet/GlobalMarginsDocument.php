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

/**
 * Spreadsheet document for the list of global margins.
 *
 * @extends AbstractArrayDocument<\App\Entity\GlobalMargin>
 */
class GlobalMarginsDocument extends AbstractArrayDocument
{
    /**
     * @param \App\Entity\GlobalMargin[] $entities
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function doRender(array $entities): bool
    {
        $this->start('globalmargin.list.title');
        $row = $this->setHeaderValues([
            'globalmargin.fields.minimum' => Alignment::HORIZONTAL_RIGHT,
            'globalmargin.fields.maximum' => Alignment::HORIZONTAL_RIGHT,
            'globalmargin.fields.delta' => Alignment::HORIZONTAL_RIGHT,
            'globalmargin.fields.margin' => Alignment::HORIZONTAL_RIGHT,
        ]);

        $this->setFormatAmount(1)
            ->setFormatAmount(2)
            ->setFormatAmount(3)
            ->setFormatPercent(4);

        foreach ($entities as $entity) {
            $this->setRowValues($row++, [
                    $entity->getMinimum(),
                    $entity->getMaximum(),
                    $entity->getDelta(),
                    $entity->getMargin(),
                ]);
        }

        $sheet = $this->getActiveSheet();
        for ($i = 1; $i < 5; ++$i) {
            $name = $this->stringFromColumnIndex($i);
            $sheet->getColumnDimension($name)
                ->setAutoSize(false)
                ->setWidth(20);
        }

        $this->finish();

        return true;
    }
}
