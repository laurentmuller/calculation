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
     */
    #[\Override]
    protected function doRender(array $entities): bool
    {
        $this->start('category.list.title');

        $sheet = $this->getActiveSheet();
        $row = $sheet->setHeaders([
            'category.fields.code' => HeaderFormat::instance(),
            'category.fields.description' => HeaderFormat::instance(),
            'category.fields.group' => HeaderFormat::instance(),
            'category.fields.products' => HeaderFormat::int(),
            'category.fields.tasks' => HeaderFormat::int(),
        ]);

        foreach ($entities as $entity) {
            $sheet->setRowValues($row++, [
                $entity->getCode(),
                $entity->getDescription(),
                $entity->getGroupCode(),
                $entity->countProducts(),
                $entity->countTasks(),
            ]);
        }
        $sheet->finish();

        return true;
    }
}
