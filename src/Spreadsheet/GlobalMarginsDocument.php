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

        $sheet = $this->getActiveSheet();
        $row = $sheet->setHeaders([
            'globalmargin.fields.minimum' => HeaderFormat::amount(),
            'globalmargin.fields.maximum' => HeaderFormat::amount(),
            'globalmargin.fields.delta' => HeaderFormat::amount(),
            'globalmargin.fields.margin' => HeaderFormat::percent(),
        ]);

        foreach ($entities as $entity) {
            $sheet->setRowValues($row++, [
                    $entity->getMinimum(),
                    $entity->getMaximum(),
                    $entity->getDelta(),
                    $entity->getMargin(),
                ]);
        }

        for ($i = 1; $i < 5; ++$i) {
            $name = $sheet->stringFromColumnIndex($i);
            $sheet->getColumnDimension($name)
                ->setAutoSize(false)
                ->setWidth(20);
        }

        $sheet->finish();

        return true;
    }
}
