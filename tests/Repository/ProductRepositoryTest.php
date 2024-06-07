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

use App\Repository\AbstractCategoryItemRepository;
use App\Repository\AbstractRepository;
use App\Repository\ProductRepository;
use App\Tests\DatabaseTrait;
use App\Tests\EntityTrait\ProductTrait;
use App\Tests\KernelServiceTestCase;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Query\Expr\OrderBy;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractRepository::class)]
#[CoversClass(AbstractCategoryItemRepository::class)]
#[CoversClass(ProductRepository::class)]
class ProductRepositoryTest extends KernelServiceTestCase
{
    use DatabaseTrait;
    use ProductTrait;

    private ProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getService(ProductRepository::class);
    }

    /**
     * @throws ORMException
     */
    protected function tearDown(): void
    {
        $this->deleteProduct();
        parent::tearDown();
    }

    /**
     * @throws ORMException
     */
    public function testCountCategoryReferences(): void
    {
        $category = $this->getCategory();
        $actual = $this->repository->countCategoryReferences($category);
        self::assertSame(0, $actual);
    }

    /**
     * @throws ORMException
     */
    public function testFindByCategory(): void
    {
        $category = $this->getCategory();
        $actual = $this->repository->findByCategory($category);
        self::assertCount(0, $actual);
        $this->getProduct();
        $actual = $this->repository->findByCategory($category);
        self::assertCount(1, $actual);
    }

    /**
     * @throws ORMException
     */
    public function testFindByDescription(): void
    {
        $actual = $this->repository->findByDescription();
        self::assertCount(0, $actual);

        $this->getProduct();
        $actual = $this->repository->findByDescription();
        self::assertCount(1, $actual);
    }

    /**
     * @throws ORMException
     */
    public function testFindByGroup(): void
    {
        $this->getGroup();
        $actual = $this->repository->findByGroup();
        self::assertCount(0, $actual);

        $this->getProduct();
        $actual = $this->repository->findByGroup();
        self::assertCount(1, $actual);
    }

    public function testGetQueryBuilderByCategory(): void
    {
        $builder = $this->repository->getQueryBuilderByCategory();
        $actual = $builder->getRootAliases()[0];
        self::assertSame('e', $actual);

        $parts = $builder->getDQLPart('orderBy');
        self::assertIsArray($parts);
        self::assertCount(3, $parts);

        /** @psalm-var OrderBy $part */
        $part = $parts[0];
        self::assertInstanceOf(OrderBy::class, $part);
        self::assertSame('c.code ASC', (string) $part);

        /** @psalm-var OrderBy $part */
        $part = $parts[1];
        self::assertInstanceOf(OrderBy::class, $part);
        self::assertSame('g.code ASC', (string) $part);

        /** @psalm-var OrderBy $part */
        $part = $parts[2];
        self::assertInstanceOf(OrderBy::class, $part);
        self::assertSame('e.description ASC', (string) $part);
    }

    public function testGetSortFields(): void
    {
        $actual = $this->repository->getSortField('group.id');
        self::assertSame('g.code', $actual);
        $actual = $this->repository->getSortField('groupCode');
        self::assertSame('g.code', $actual);
        $actual = $this->repository->getSortField('group.code');
        self::assertSame('g.code', $actual);

        $actual = $this->repository->getSortField('category.id');
        self::assertSame('c.code', $actual);
        $actual = $this->repository->getSortField('categoryCode');
        self::assertSame('c.code', $actual);
        $actual = $this->repository->getSortField('category.code');
        self::assertSame('c.code', $actual);
    }

    public function testGetTableQueryBuilder(): void
    {
        $builder = $this->repository->getTableQueryBuilder();
        $actual = $builder->getRootAliases()[0];
        self::assertSame('e', $actual);
    }

    /**
     * @throws ORMException
     */
    public function testSearch(): void
    {
        $actual = $this->repository->search('fake');
        self::assertCount(0, $actual);

        $this->getProduct();
        $actual = $this->repository->search('Test');
        self::assertCount(1, $actual);
    }
}
