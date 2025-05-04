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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @phpstan-require-extends TestCase
 */
trait ManagerRegistryTrait
{
    private (EntityManager&MockObject)|null $entityManager = null;
    private (ManagerRegistry&MockObject)|null $managerRegistry = null;
    /** @var array<string, mixed> */
    private array $repositories = [];

    /**
     * @param class-string $entityClass
     * @param class-string $repositoryClass
     */
    protected function createManagerRegistry(
        string $entityClass,
        string $repositoryClass,
        string $queryMethod,
        array $results
    ): MockObject&ManagerRegistry {
        $query = $this->createQuery($results);
        $builder = $this->createQueryBuilder($query);
        $this->repositories[$entityClass] = $this->createRepository($repositoryClass, $queryMethod, $builder);

        return $this->getManagerRegistry();
    }

    /**
     * @phpstan-return MockObject&Query<array-key, mixed>
     */
    private function createQuery(array $results): MockObject&Query
    {
        $query = $this->createMock(Query::class);
        $query->method('execute')
            ->willReturn($results);

        return $query;
    }

    /**
     * @phpstan-param  MockObject&Query<array-key, mixed> $query
     */
    private function createQueryBuilder(MockObject&Query $query): MockObject&QueryBuilder
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
     * @phpstan-param class-string $repositoryClass
     */
    private function createRepository(
        string $repositoryClass,
        string $queryMethod,
        MockObject&QueryBuilder $builder
    ): MockObject {
        $repository = $this->createMock($repositoryClass);
        $repository->method($queryMethod)
            ->willReturn($builder);

        return $repository;
    }

    private function getEntityManager(): MockObject&EntityManager
    {
        if (null === $this->entityManager) {
            $this->entityManager = $this->createMock(EntityManager::class);
            $this->entityManager->method('getRepository')
                ->willReturnCallback(fn (string $className): mixed => $this->repositories[$className] ?? null);
        }

        return $this->entityManager;
    }

    private function getManagerRegistry(): MockObject&ManagerRegistry
    {
        if (null === $this->managerRegistry) {
            $this->managerRegistry = $this->createMock(ManagerRegistry::class);
            $this->managerRegistry->method('getManagerForClass')
                ->willReturn($this->getEntityManager());
        }

        return $this->managerRegistry;
    }
}
