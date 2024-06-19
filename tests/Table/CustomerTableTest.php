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

namespace App\Tests\Table;

use App\Entity\Customer;
use App\Repository\AbstractRepository;
use App\Repository\CustomerRepository;
use App\Table\AbstractEntityTable;
use App\Table\AbstractTable;
use App\Table\CustomerTable;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @extends EntityTableTestCase<Customer, CustomerRepository, CustomerTable>
 */
#[CoversClass(AbstractTable::class)]
#[CoversClass(AbstractEntityTable::class)]
#[CoversClass(CustomerTable::class)]
class CustomerTableTest extends EntityTableTestCase
{
    protected function createEntities(): array
    {
        $entity = new Customer();
        $entity->setCompany('Company')
            ->setFirstName('First Name')
            ->setLastName('Last Name')
            ->setAddress('Address')
            ->setZipCode('ZipCode')
            ->setCity('City');

        return [$entity];
    }

    /**
     * @throws Exception
     */
    protected function createRepository(MockObject&QueryBuilder $queryBuilder): MockObject&CustomerRepository
    {
        $repository = $this->createMock(CustomerRepository::class);
        $repository->method('createDefaultQueryBuilder')
            ->willReturn($queryBuilder);

        return $repository;
    }

    /**
     * @psalm-param CustomerRepository $repository
     */
    protected function createTable(AbstractRepository $repository): CustomerTable
    {
        return new CustomerTable($repository);
    }
}
