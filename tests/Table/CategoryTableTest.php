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
use App\Table\CategoryTable;
use App\Table\DataQuery;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

/**
 * @extends EntityTableTestCase<Category, CategoryRepository, CategoryTable>
 */
class CategoryTableTest extends EntityTableTestCase
{
    /**
     * @throws Exception|\ReflectionException
     */
    public function testWithGroupId(): void
    {
        $dataQuery = new DataQuery();
        $dataQuery->groupId = 10;
        $this->processDataQuery($dataQuery);
    }

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

    protected function createRepository(MockObject&QueryBuilder $queryBuilder): MockObject&CategoryRepository
    {
        $repository = $this->createMock(CategoryRepository::class);
        $repository->method('getTableQueryBuilder')
            ->willReturn($queryBuilder);

        return $repository;
    }

    /**
     * @psalm-param CategoryRepository $repository
     *
     * @throws Exception
     */
    protected function createTable(AbstractRepository $repository): CategoryTable
    {
        $twig = $this->createMock(Environment::class);
        $groupRepository = $this->createMock(GroupRepository::class);
        $checker = $this->createMock(AuthorizationCheckerInterface::class);

        $table = new CategoryTable($repository, $twig, $groupRepository);
        $table->setChecker($checker);

        return $table;
    }
}
