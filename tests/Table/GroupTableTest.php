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

use App\Entity\Group;
use App\Repository\AbstractRepository;
use App\Repository\GroupRepository;
use App\Service\IndexService;
use App\Table\GroupTable;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;
use Twig\Error\Error;

/**
 * @extends EntityTableTestCase<Group, GroupRepository, GroupTable>
 */
final class GroupTableTest extends EntityTableTestCase
{
    /**
     * @throws Error
     */
    public function testFormats(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->method('render')
            ->willReturnArgument(0);
        $table = new GroupTable(
            self::createStub(GroupRepository::class),
            $twig,
            self::createStub(IndexService::class),
        );
        $table->setChecker(self::createStub(AuthorizationCheckerInterface::class));

        $expected = 'macros/_cell_table_link.html.twig';
        $actual = $table->formatProducts(0, ['id' => 1]);
        self::assertSame($expected, $actual);

        $actual = $table->formatCategories(0, ['id' => 1]);
        self::assertSame($expected, $actual);

        $actual = $table->formatTasks(0, ['id' => 1]);
        self::assertSame($expected, $actual);
    }

    #[\Override]
    protected function createEntities(): array
    {
        $entity = [
            'id' => 1,
            'code' => 'code',
            'description' => 'description',
            'margins' => 1,
            'categories' => 2,
            'products' => 3,
            'tasks' => 4,
        ];

        return [$entity];
    }

    #[\Override]
    protected function createMockRepository(QueryBuilder $queryBuilder): MockObject&GroupRepository
    {
        $repository = $this->createMock(GroupRepository::class);
        $repository->method('getTableQueryBuilder')
            ->willReturn($queryBuilder);

        return $repository;
    }

    /**
     * @phpstan-param GroupRepository $repository
     */
    #[\Override]
    protected function createTable(AbstractRepository $repository): GroupTable
    {
        $table = new GroupTable(
            $repository,
            self::createStub(Environment::class),
            $this->createMockIndexService()
        );
        $table->setChecker(self::createStub(AuthorizationCheckerInterface::class));

        return $table;
    }
}
