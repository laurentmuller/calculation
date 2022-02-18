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
 * Spreadsheet document for the list of customers.
 *
 * @author Laurent Muller
 *
 * @extends AbstractArrayDocument<\App\Entity\Customer>
 */
class CustomersDocument extends AbstractArrayDocument
{
    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        // initialize
        $this->start('customer.list.title');

        // headers
        $this->setHeaderValues([
            'customer.fields.lastName' => Alignment::HORIZONTAL_GENERAL,
            'customer.fields.firstName' => Alignment::HORIZONTAL_GENERAL,
            'customer.fields.company' => Alignment::HORIZONTAL_GENERAL,
            'customer.fields.address' => Alignment::HORIZONTAL_GENERAL,
            'customer.fields.zipCode' => Alignment::HORIZONTAL_RIGHT,
            'customer.fields.city' => Alignment::HORIZONTAL_GENERAL,
        ]);

        // rows
        $row = 2;
        foreach ($entities as $entity) {
            $this->setRowValues($row++, [
                $entity->getLastName(),
                $entity->getFirstName(),
                $entity->getCompany(),
                $entity->getAddress(),
                $entity->getZipCode(),
                $entity->getCity(),
            ]);
        }

        $this->finish();

        return true;
    }
}
