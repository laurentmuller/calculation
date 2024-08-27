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
 * Spreadsheet document for the list of customers.
 *
 * @extends AbstractArrayDocument<\App\Entity\Customer>
 */
class CustomersDocument extends AbstractArrayDocument
{
    /**
     * @param \App\Entity\Customer[] $entities
     */
    protected function doRender(array $entities): bool
    {
        $this->start('customer.list.title');

        $sheet = $this->getActiveSheet();
        $row = $sheet->setHeaders([
            'customer.fields.lastName' => HeaderFormat::instance(),
            'customer.fields.firstName' => HeaderFormat::instance(),
            'customer.fields.company' => HeaderFormat::instance(),
            'customer.fields.address' => HeaderFormat::instance(),
            'customer.fields.zipCode' => HeaderFormat::right(),
            'customer.fields.city' => HeaderFormat::instance(),
        ]);

        foreach ($entities as $entity) {
            $sheet->setRowValues($row++, [
                $entity->getLastName(),
                $entity->getFirstName(),
                $entity->getCompany(),
                $entity->getAddress(),
                $entity->getZipCode(),
                $entity->getCity(),
            ]);
        }
        $sheet->finish();

        return true;
    }
}
