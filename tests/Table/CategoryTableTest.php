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
use App\Repository\AbstractRepository;
use App\Repository\CategoryRepository;
use App\Repository\GroupRepository;
use App\Service\IndexService;
use App\Table\CategoryTable;
use App\Table\DataQuery;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;
use Twig\Error\Error;

/**
 * @extends EntityTableTestCase<Category, CategoryRepository, CategoryTable>
 */
final class CategoryTableTest extends EntityTableTestCase
{
    /**
     * @throws Error
     */
    public function testFormats(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->method('render')
            ->willReturnArgument(0);
        $table = new CategoryTable(
            self::createStub(CategoryRepository::class),
            $twig,
            self::createStub(GroupRepository::class),
            self::createStub(IndexService::class),
        );
        $table->setChecker(self::createStub(AuthorizationCheckerInterface::class));

        $expected = 'macros/_cell_table_link.html.twig';
        $actual = $table->formatProducts(0, ['id' => 1]);
        self::assertSame($expected, $actual);

        $actual = $table->formatTasks(0, ['id' => 1]);
        self::assertSame($expected, $actual);
    }

    public function testWithCallback(): void
    {
        $parameters = ['groupId' => 10];
        $dataQuery = new DataQuery();
        $dataQuery->callback = true;
        $this->updateQueryParameters($dataQuery, $parameters);
        $this->processDataQuery($dataQuery);
    }

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
        $entityEmpty = [
            'id' => 1,
            'code' => 'code1',
            'description' => 'description1',
            'groupCode' => 'groupCode1',
            'products' => 0,
            'tasks' => 0,
        ];
        $entityCount = [
            'id' => 2,
            'code' => 'code2',
            'description' => 'description2',
            'groupCode' => 'groupCode2',
            'products' => 10,
            'tasks' => 10,
        ];

        return [$entityEmpty, $entityCount];
    }

    #[\Override]
    protected function createMockRepository(MockObject&QueryBuilder $queryBuilder): MockObject&CategoryRepository
    {
        $repository = $this->createMock(CategoryRepository::class);
        $repository->method('getTableQueryBuilder')
            ->willReturn($queryBuilder);

        return $repository;
    }

    /**
     * @phpstan-param CategoryRepository $repository
     */
    #[\Override]
    protected function createTable(AbstractRepository $repository): CategoryTable
    {
        $table = new CategoryTable(
            $repository,
            self::createStub(Environment::class),
            self::createStub(GroupRepository::class),
            $this->createMockIndexService()
        );
        $table->setChecker(self::createStub(AuthorizationCheckerInterface::class));

        return $table;
    }
}
