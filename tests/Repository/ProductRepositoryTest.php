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

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Tests\EntityTrait\ProductTrait;
use Doctrine\ORM\Query\Expr\OrderBy;

/**
 * @extends AbstractRepositoryTestCase<Product, ProductRepository>
 */
final class ProductRepositoryTest extends AbstractRepositoryTestCase
{
    use ProductTrait;

    #[\Override]
    protected function tearDown(): void
    {
        $this->deleteProduct();
        parent::tearDown();
    }

    public function testCountCategoryReferences(): void
    {
        $category = $this->getCategory();
        $actual = $this->repository->countCategoryReferences($category);
        self::assertSame(0, $actual);
    }

    public function testFindByCategory(): void
    {
        $category = $this->getCategory();
        $actual = $this->repository->findByCategory($category);
        self::assertEmpty($actual);
        $this->getProduct();
        $actual = $this->repository->findByCategory($category);
        self::assertCount(1, $actual);
    }

    public function testFindByDescription(): void
    {
        $actual = $this->repository->findByDescription();
        self::assertEmpty($actual);

        $this->getProduct();
        $actual = $this->repository->findByDescription();
        self::assertCount(1, $actual);
    }

    public function testFindByGroup(): void
    {
        $this->getGroup();
        $actual = $this->repository->findByGroup();
        self::assertEmpty($actual);

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

        $part = $parts[0];
        self::assertInstanceOf(OrderBy::class, $part);
        self::assertSame('c.code ASC', (string) $part);

        $part = $parts[1];
        self::assertInstanceOf(OrderBy::class, $part);
        self::assertSame('g.code ASC', (string) $part);

        $part = $parts[2];
        self::assertInstanceOf(OrderBy::class, $part);
        self::assertSame('e.description ASC', (string) $part);
    }

    public function testGetSearchField(): void
    {
        $this->assertSameSearchField('group.id', 'g.id');
        $this->assertSameSearchField('groupCode', 'g.code');
        $this->assertSameSearchField('group.code', 'g.code');

        $this->assertSameSearchField('category.id', 'c.id');
        $this->assertSameSearchField('categoryCode', 'c.code');
        $this->assertSameSearchField('category.code', 'c.code');
    }

    public function testGetSortFields(): void
    {
        $this->assertSameSortField('group.id', 'g.code');
        $this->assertSameSortField('groupCode', 'g.code');
        $this->assertSameSortField('group.code', 'g.code');

        $this->assertSameSortField('category.id', 'c.code');
        $this->assertSameSortField('categoryCode', 'c.code');
        $this->assertSameSortField('category.code', 'c.code');
    }

    public function testGetTableQueryBuilder(): void
    {
        $builder = $this->repository->getTableQueryBuilder();
        $actual = $builder->getRootAliases()[0];
        self::assertSame('e', $actual);
    }

    public function testSearch(): void
    {
        $actual = $this->repository->search('fake');
        self::assertEmpty($actual);

        $this->getProduct();
        $actual = $this->repository->search('Test');
        self::assertCount(1, $actual);
    }

    #[\Override]
    protected function getRepositoryClass(): string
    {
        return ProductRepository::class;
    }
}
