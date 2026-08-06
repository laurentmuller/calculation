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

use App\Interfaces\EntityInterface;
use App\Repository\AbstractRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

/**
 * @phpstan-require-extends TestCase
 */
trait ManagerRegistryTrait
{
    private (Stub&EntityManager)|null $entityManager = null;
    private (Stub&ManagerRegistry)|null $managerRegistry = null;
    /** @var array<class-string<EntityInterface>, Stub> */
    private array $repositories = [];

    /**
     * @template TEntity of EntityInterface
     * @template TRepository of AbstractRepository<TEntity>
     *
     * @param class-string<TEntity>     $entityClass
     * @param class-string<TRepository> $repositoryClass
     */
    protected function createManagerRegistry(
        string $entityClass,
        string $repositoryClass,
        string $queryMethod,
        array $results
    ): Stub&ManagerRegistry {
        $query = $this->createQuery($results);
        $builder = $this->createQueryBuilder($query);
        $this->repositories[$entityClass] = $this->createRepository($repositoryClass, $queryMethod, $builder);

        return $this->getManagerRegistry();
    }

    /**
     * @return Stub&Query<array-key, mixed>
     */
    private function createQuery(array $results): Stub&Query
    {
        $query = self::createStub(Query::class);
        $query->method('execute')
            ->willReturn($results);

        return $query;
    }

    /**
     * @param Stub&Query<array-key, mixed> $query
     */
    private function createQueryBuilder(Query $query): Stub&QueryBuilder
    {
        $parameters = new ArrayCollection();
        $builder = self::createStub(QueryBuilder::class);
        $builder->method('getParameters')
            ->willReturn($parameters);
        $builder->method('getQuery')
            ->willReturn($query);

        return $builder;
    }

    /**
     * @template TEntity of EntityInterface
     * @template TRepository of AbstractRepository<TEntity>
     *
     * @param class-string<TRepository> $repositoryClass
     *
     * @return Stub&TRepository
     */
    private function createRepository(
        string $repositoryClass,
        string $queryMethod,
        QueryBuilder $builder
    ): Stub {
        $repository = self::createStub($repositoryClass);
        $repository->method($queryMethod)
            ->willReturn($builder);

        return $repository;
    }

    private function getEntityManager(): Stub&EntityManager
    {
        if (null === $this->entityManager) {
            $this->entityManager = self::createStub(EntityManager::class);
            $this->entityManager->method('getRepository')
                ->willReturnCallback(fn (string $className): ?Stub => $this->repositories[$className] ?? null);
        }

        return $this->entityManager;
    }

    private function getManagerRegistry(): Stub&ManagerRegistry
    {
        if (null === $this->managerRegistry) {
            $this->managerRegistry = self::createStub(ManagerRegistry::class);
            $this->managerRegistry->method('getManagerForClass')
                ->willReturn($this->getEntityManager());
        }

        return $this->managerRegistry;
    }
}
