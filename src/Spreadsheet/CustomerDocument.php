<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Spreadsheet;

use App\Entity\Customer;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Excel document for the list of customers.
 *
 * @author Laurent Muller
 */
class CustomerDocument extends AbstractArrayDocument
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
        /** @var Customer $entity */
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
