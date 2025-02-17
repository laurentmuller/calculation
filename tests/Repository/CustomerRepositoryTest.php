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

namespace App\Tests\Repository;

use App\Repository\CustomerRepository;
use App\Tests\DatabaseTrait;
use App\Tests\KernelServiceTestCase;

class CustomerRepositoryTest extends KernelServiceTestCase
{
    use DatabaseTrait;

    private CustomerRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getService(CustomerRepository::class);
    }

    public function testFindByNameAndCompany(): void
    {
        $actual = $this->repository->findByNameAndCompany();
        self::assertEmpty($actual);
    }

    public function testGetSearchFields(): void
    {
        $actual = $this->repository->getSearchFields(CustomerRepository::NAME_COMPANY_FIELD);
        self::assertIsArray($actual);
        self::assertCount(3, $actual);
        self::assertSame('e.lastName', $actual[0]);
        self::assertSame('e.firstName', $actual[1]);
        self::assertSame('e.company', $actual[2]);

        $actual = $this->repository->getSearchFields(CustomerRepository::ZIP_CITY_FIELD);
        self::assertIsArray($actual);
        self::assertCount(2, $actual);
        self::assertSame('e.zipCode', $actual[0]);
        self::assertSame('e.city', $actual[1]);
    }

    public function testGetSortFields(): void
    {
        $actual = $this->repository->getSortField(CustomerRepository::NAME_COMPANY_FIELD);
        self::assertSame("CONCAT(COALESCE(e.lastName, ''),COALESCE(e.firstName, ''),COALESCE(e.company, ''))", $actual);

        $actual = $this->repository->getSortField(CustomerRepository::ZIP_CITY_FIELD);
        self::assertSame("CONCAT(COALESCE(e.zipCode, ''),COALESCE(e.city, ''))", $actual);
    }
}
