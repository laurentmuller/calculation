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

namespace App\Tests\Controller;

use App\Entity\Customer;
use Symfony\Component\HttpFoundation\Response;

class CustomerControllerTest extends EntityControllerTestCase
{
    private ?Customer $customer = null;

    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/customer', self::ROLE_USER];
        yield ['/customer', self::ROLE_ADMIN];
        yield ['/customer', self::ROLE_SUPER_ADMIN];

        yield ['/customer/add', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/customer/add', self::ROLE_ADMIN];
        yield ['/customer/add', self::ROLE_SUPER_ADMIN];

        yield ['/customer/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/customer/edit/1', self::ROLE_ADMIN];
        yield ['/customer/edit/1', self::ROLE_SUPER_ADMIN];

        yield ['/customer/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/customer/delete/1', self::ROLE_ADMIN];
        yield ['/customer/delete/1', self::ROLE_SUPER_ADMIN];

        yield ['/customer/show/1', self::ROLE_USER];
        yield ['/customer/show/1', self::ROLE_ADMIN];
        yield ['/customer/show/1', self::ROLE_SUPER_ADMIN];

        yield ['/customer/pdf', self::ROLE_USER];
        yield ['/customer/pdf', self::ROLE_ADMIN];
        yield ['/customer/pdf', self::ROLE_SUPER_ADMIN];

        yield ['/customer/excel', self::ROLE_USER];
        yield ['/customer/excel', self::ROLE_ADMIN];
        yield ['/customer/excel', self::ROLE_SUPER_ADMIN];
    }

    public function testAdd(): void
    {
        $data = [
            'customer[company]' => 'Company',
            'customer[firstName]' => 'First Name',
            'customer[lastName]' => 'Last Name',
        ];
        $this->checkAddEntity('/customer/add', $data);
    }

    public function testDelete(): void
    {
        $uri = \sprintf('/customer/delete/%d', (int) $this->getCustomer()->getId());
        $this->checkDeleteEntity($uri);
    }

    public function testEdit(): void
    {
        $uri = \sprintf('/customer/edit/%d', (int) $this->getCustomer()->getId());
        $data = [
            'customer[company]' => 'New Company',
            'customer[firstName]' => 'New First Name',
            'customer[lastName]' => 'New Last Name',
        ];
        $this->checkEditEntity($uri, $data);
    }

    public function testExcelEmpty(): void
    {
        $this->checkUriWithEmptyEntity('/customer/excel', Customer::class);
    }

    public function testPdfEmpty(): void
    {
        $this->checkUriWithEmptyEntity('/customer/pdf', Customer::class);
    }

    #[\Override]
    protected function addEntities(): void
    {
        $this->getCustomer();
    }

    #[\Override]
    protected function deleteEntities(): void
    {
        $this->customer = $this->deleteEntity($this->customer);
    }

    private function getCustomer(): Customer
    {
        if ($this->customer instanceof Customer) {
            return $this->customer;
        }

        $this->customer = new Customer();
        $this->customer->setCompany('Test Company');

        return $this->addEntity($this->customer);
    }
}
