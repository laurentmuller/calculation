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

namespace App\Faker;

use App\Interfaces\EntityInterface;
use App\Repository\AbstractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Provider\Base;

/**
 * Entity provider.
 *
 * @template TEntity of EntityInterface
 *
 * @property \Faker\UniqueGenerator $unique
 */
class EntityProvider extends Base
{
    /**
     * The cached distinct values.
     *
     * @var array<string, array>
     */
    private array $distinctValues = [];

    /**
     * The cached entities.
     *
     * @psalm-var TEntity[]
     */
    private ?array $entities = null;

    /**
     * The repository.
     *
     * @psalm-var AbstractRepository<TEntity>
     */
    private readonly AbstractRepository $repository;

    /**
     * @psalm-param class-string<TEntity> $className the entity class name.
     */
    public function __construct(Generator $generator, EntityManagerInterface $manager, string $className)
    {
        parent::__construct($generator);

        /** @psalm-var AbstractRepository<TEntity> $repository */
        $repository = $manager->getRepository($className);

        $this->repository = $repository;
    }

    /**
     * Gets the number of entities.
     */
    protected function count(): int
    {
        return \count($this->getEntities());
    }

    /**
     * Gets a random value for the given column.
     *
     * @param string $field     the field name (column) to get values for
     * @param bool   $allowNull true to allow the return of a null value
     *
     * @return mixed|null a random value or null if none
     */
    protected function distinctValue(string $field, bool $allowNull = false): mixed
    {
        // already loaded?
        if (!\array_key_exists($field, $this->distinctValues) || empty($this->distinctValues[$field])) {
            $this->distinctValues[$field] = $this->getRepository()->getDistinctValues($field);
        }

        if ($allowNull) {
            return static::randomElement(\array_merge($this->distinctValues[$field], [null]));
        }

        return static::randomElement($this->distinctValues[$field]);
    }

    /**
     * Gets a random entity.
     *
     * @psalm-return TEntity|null
     */
    protected function entity()
    {
        /** @psalm-var TEntity|null $entity */
        $entity = static::randomElement($this->getEntities());

        return $entity;
    }

    /**
     * Gets the criteria used to filter entities.
     */
    protected function getCriteria(): array
    {
        return [];
    }

    /**
     * Gets all entities.
     *
     * @psalm-return TEntity[]
     */
    protected function getEntities(): array
    {
        if (empty($this->entities)) {
            $criteria = $this->getCriteria();
            $repository = $this->getRepository();
            $this->entities = [] === $criteria ? $repository->findAll() : $repository->findBy($criteria);
        }

        return $this->entities;
    }

    /**
     * @return AbstractRepository<TEntity>
     */
    protected function getRepository(): AbstractRepository
    {
        return $this->repository;
    }
}
