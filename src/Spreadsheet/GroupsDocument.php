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
 * Spreadsheet document for the list of groups.
 *
 * @extends AbstractArrayDocument<\App\Entity\Group>
 */
class GroupsDocument extends AbstractArrayDocument
{
    /**
     * @param \App\Entity\Group[] $entities
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function doRender(array $entities): bool
    {
        $this->start('group.list.title');

        $sheet = $this->getActiveSheet();
        $row = $sheet->setHeaders([
            'group.fields.code' => HeaderFormat::instance(),
            'group.fields.description' => HeaderFormat::instance(),
            'group.fields.categories' => HeaderFormat::int(),
            'category.fields.products' => HeaderFormat::int(),
            'category.fields.tasks' => HeaderFormat::int(),
            'globalmargin.fields.minimum' => HeaderFormat::amount(),
            'globalmargin.fields.maximum' => HeaderFormat::amount(),
            'globalmargin.fields.margin' => HeaderFormat::percent(),
        ]);

        foreach ($entities as $entity) {
            $sheet->setRowValues($row, [
                $entity->getCode(),
                $entity->getDescription(),
                $entity->countCategories(),
                $entity->countProducts(),
                $entity->countTasks(),
            ]);
            if ($entity->hasMargins()) {
                $first = true;
                foreach ($entity->getMargins() as $margin) {
                    if (!$first) {
                        ++$row;
                    }
                    $sheet->setRowValues($row, [
                        $margin->getMinimum(),
                        $margin->getMaximum(),
                        $margin->getMargin(),
                    ], 6);
                    $first = false;
                }
            } else {
                $sheet->setCellValue([6, $row], $this->trans('group.edit.empty_margins'));
                $sheet->mergeContent(6, 8, $row);
            }
            ++$row;
        }
        $sheet->finish();

        return true;
    }
}
