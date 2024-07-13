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

namespace App\Tests\Form\Customer;

use App\Entity\Customer;
use App\Form\Customer\CustomerType;
use App\Form\Type\CountryFlagType;
use App\Service\CountryFlagService;
use App\Tests\Form\EntityTypeTestCase;

/**
 * @extends EntityTypeTestCase<Customer, CustomerType>
 */
class CustomerTypeTest extends EntityTypeTestCase
{
    protected function getData(): array
    {
        return [
            'company' => 'company',
            'firstName' => 'firstName',
            'lastName' => 'lastName',
            'title' => 'title',
            'address' => 'address',
            'zipCode' => 'zipCode',
            'city' => 'city',
            'country' => 'CH',
            'email' => 'email@email.com',
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

    protected function getPreloadedExtensions(): array
    {
        return [
            new CountryFlagType(new CountryFlagService()),
        ];
    }
}
