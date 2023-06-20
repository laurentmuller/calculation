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
 * Spreadsheet document for the list of categories.
 *
 * @extends AbstractArrayDocument<\App\Entity\Category>
 */
class CategoriesDocument extends AbstractArrayDocument
{
    /**
     * @param \App\Entity\Category[] $entities
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function doRender(array $entities): bool
    {
        $this->start('category.list.title');

        $row = $this->setHeaders([
            'category.fields.code' => HeaderFormat::instance(),
            'category.fields.description' => HeaderFormat::instance(),
            'category.fields.group' => HeaderFormat::instance(),
            'category.fields.products' => HeaderFormat::int(),
            'category.fields.tasks' => HeaderFormat::int(),
        ]);

        $default = $this->trans('report.other');
        foreach ($entities as $entity) {
            $this->setRowValues($row++, [
                $entity->getCode(),
                $entity->getDescription(),
                $entity->getGroupCode() ?? $default,
                $entity->countProducts(),
                $entity->countTasks(),
            ]);
        }
        $this->finish();

        return true;
    }
}
