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

use App\Entity\Category;
use App\Entity\Group;
use App\Entity\Product;
use App\Repository\AbstractRepository;
use App\Repository\CategoryRepository;
use App\Repository\GroupRepository;
use App\Repository\ProductRepository;
use App\Table\DataQuery;
use App\Table\ProductTable;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @extends EntityTableTestCase<Product, ProductRepository, ProductTable>
 */
final class ProductTableTest extends EntityTableTestCase
{
    private int $categoryId;
    private int $groupId;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->categoryId = 0;
        $this->groupId = 0;
    }

    /**
     * @throws \ReflectionException
     */
    public function testWithCallback(): void
    {
        $parameters = ['categoryId' => 10];
        $dataQuery = new DataQuery();
        $dataQuery->callback = true;
        $this->updateQueryParameters($dataQuery, $parameters);
        $this->processDataQuery($dataQuery);
    }

    /**
     * @throws \ReflectionException
     */
    public function testWithCategoryId(): void
    {
        $parameters = ['categoryId' => 10];
        $dataQuery = new DataQuery();
        $this->updateQueryParameters($dataQuery, $parameters);
        $this->processDataQuery($dataQuery);
    }

    /**
     * @throws \ReflectionException
     */
    public function testWithFindCategoryId(): void
    {
        $this->categoryId = 10;
        $parameters = ['categoryId' => 10];
        $dataQuery = new DataQuery();
        $this->updateQueryParameters($dataQuery, $parameters);
        $this->processDataQuery($dataQuery);
    }

    /**
     * @throws \ReflectionException
     */
    public function testWithFindGroupId(): void
    {
        $this->groupId = 10;
        $parameters = ['groupId' => 10];
        $dataQuery = new DataQuery();
        $this->updateQueryParameters($dataQuery, $parameters);
        $this->processDataQuery($dataQuery);
    }

    /**
     * @throws \ReflectionException
     */
    public function testWithGroupId(): void
    {
        $parameters = ['groupId' => 10];
        $dataQuery = new DataQuery();
        $this->updateQueryParameters($dataQuery, $parameters);
        $this->processDataQuery($dataQuery);
    }

    #[\Override]
    protected function createEntities(): array
    {
        $entity = [
            'id' => 1,
            'description' => 'description',
            'price' => 12.5,
            'unit' => 'unit',
            'supplier' => 'supplier',
            'groupCode' => 'groupCode',
            'categoryCode' => 'categoryCode',
        ];

        return [$entity];
    }

    #[\Override]
    protected function createMockRepository(MockObject&QueryBuilder $queryBuilder): MockObject&ProductRepository
    {
        $repository = $this->createMock(ProductRepository::class);
        $repository->method('getTableQueryBuilder')
            ->willReturn($queryBuilder);

        return $repository;
    }

    /**
     * @phpstan-param ProductRepository $repository
     *
     * @throws \ReflectionException
     */
    #[\Override]
    protected function createTable(AbstractRepository $repository): ProductTable
    {
        $categoryRepository = $this->createMock(CategoryRepository::class);
        if (0 !== $this->categoryId) {
            $category = new Category();
            $category->setCode('code');
            self::setId($category);
            $categoryRepository->method('find')
                ->willReturn($category);
        }

        $groupRepository = $this->createMock(GroupRepository::class);
        if (0 !== $this->groupId) {
            $category = new Group();
            $category->setCode('code');
            self::setId($category);
            $groupRepository->method('find')
                ->willReturn($category);
        }

        $service = $this->createMockIndexService();

        return new ProductTable($repository, $categoryRepository, $groupRepository, $service);
    }
}
