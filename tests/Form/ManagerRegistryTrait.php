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

namespace App\Tests\Form;

use App\Repository\AbstractRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-require-extends TestCase
 */
trait ManagerRegistryTrait
{
    /**
     * @psalm-param class-string $entityClassName
     *
     * @throws Exception
     */
    protected function createEntityManager($entityClassName): MockObject&EntityManager
    {
        $manager = $this->createMock(EntityManager::class);
        $manager->method('getClassMetadata')
            ->willReturn(new ClassMetadata($entityClassName));

        return $manager;
    }

    /**
     * @throws Exception
     */
    protected function createQuery(array $results): MockObject&Query
    {
        $query = $this->createMock(Query::class);
        $query->method('execute')
            ->willReturn($results);

        return $query;
    }

    /**
     * @throws Exception
     */
    protected function createQueryBuilder(MockObject&Query $query): MockObject&QueryBuilder
    {
        $parameters = new ArrayCollection();
        $builder = $this->createMock(QueryBuilder::class);
        $builder->method('getParameters')
            ->willReturn($parameters);
        $builder->method('getQuery')
            ->willReturn($query);

        return $builder;
    }

    /**
     * @throws Exception
     */
    protected function createRegistry(MockObject&EntityManager $manager): MockObject&ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')
            ->willReturn($manager);

        return $registry;
    }

    /**
     * @template TRepository of AbstractRepository
     *
     * @psalm-param class-string<TRepository> $repositoryClass
     *
     * @psalm-return MockObject&TRepository
     *
     * @throws Exception
     */
    protected function createRepository(string $repositoryClass): MockObject&AbstractRepository
    {
        return $this->createMock($repositoryClass);
    }
}
