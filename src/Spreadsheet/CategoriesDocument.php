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

use App\Entity\Category;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Spreadsheet document for the list of categories.
 *
 * @author Laurent Muller
 */
class CategoriesDocument extends AbstractArrayDocument
{
    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        // initialize
        $this->start('category.list.title');

        // headers
        $this->setHeaderValues([
            'category.fields.code' => Alignment::HORIZONTAL_GENERAL,
            'category.fields.description' => Alignment::HORIZONTAL_GENERAL,
            'category.fields.group' => Alignment::HORIZONTAL_GENERAL,
            'category.fields.products' => Alignment::HORIZONTAL_RIGHT,
            'category.fields.tasks' => Alignment::HORIZONTAL_RIGHT,
        ]);

        // formats
        $this->setFormatInt(4);

        // rows
        $row = 2;
        $default = $this->translator->trans('report.other');
        /** @var Category $entity */
        foreach ($entities as $entity) {
            $this->setRowValues($row++, [
                $entity->getCode(),
                $entity->getDescription(),
                $entity->getGroupCode() ?: $default,
                $entity->countProducts(),
                $entity->countTasks(),
            ]);
        }

        $this->finish();

        return true;
    }
}
