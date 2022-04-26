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

namespace App\Tests\Form;

use App\Entity\Customer;
use App\Form\Customer\CustomerType;

/**
 * Test for the {@link App\Form\Customer\CustomerType} class.
 */
class CustomerTypeTest extends AbstractEntityTypeTestCase
{
    protected function getData(): array
    {
        return [
            'company' => 'company',
            'firstName' => 'firstName',
            'lastName' => 'lastName',
            'title' => 'title',
            'address' => 'address',
            'city' => 'city',
            'zipCode' => 'zipCode',
            'email' => 'email',
        ];
    }

    protected function getEntityClass(): string
    {
        return Customer::class;
    }

    protected function getFormTypeClass(): string
    {
        return CustomerType::class;
    }
}
