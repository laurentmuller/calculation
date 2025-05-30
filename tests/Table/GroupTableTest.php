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
use App\Table\GroupTable;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

/**
 * @extends EntityTableTestCase<Group, GroupRepository, GroupTable>
 */
class GroupTableTest extends EntityTableTestCase
{
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
    protected function createMockRepository(MockObject&QueryBuilder $queryBuilder): MockObject&GroupRepository
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
        $twig = $this->createMock(Environment::class);
        $checker = $this->createMock(AuthorizationCheckerInterface::class);

        $table = new GroupTable($repository, $twig);
        $table->setChecker($checker);

        return $table;
    }
}
