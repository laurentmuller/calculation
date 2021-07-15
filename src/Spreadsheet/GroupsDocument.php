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

use App\Entity\Group;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Excel document for the list of groups.
 *
 * @author Laurent Muller
 */
class GroupsDocument extends AbstractArrayDocument
{
    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        // initialize
        $this->start('group.list.title');

        // headers
        $this->setHeaderValues([
            'group.fields.code' => Alignment::HORIZONTAL_GENERAL,
            'group.fields.description' => Alignment::HORIZONTAL_GENERAL,
            'group.fields.margins' => Alignment::HORIZONTAL_RIGHT,
            'group.fields.categories' => Alignment::HORIZONTAL_RIGHT,
        ]);

        // formats
        $this->setFormatInt(3)
            ->setFormatInt(4);

        // rows
        $row = 2;
        /** @var Group $entity */
        foreach ($entities as $entity) {
            $this->setRowValues($row++, [
                $entity->getCode(),
                $entity->getDescription(),
                $entity->countMargins(),
                $entity->countCategories(),
            ]);
        }

        $this->finish();

        return true;
    }
}
