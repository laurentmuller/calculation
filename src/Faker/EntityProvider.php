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

use App\Repository\AbstractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Provider\Base;

/**
 * Entity provider.
 *
 * @author Laurent Muller
 *
 * @template T of \App\Entity\AbstractEntity
 * @psalm-suppress PropertyNotSetInConstructor
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
     * @psalm-var T[]
     */
    private ?array $entities = null;

    /**
     * The repository.
     *
     * @psalm-var AbstractRepository<T>
     */
    private readonly AbstractRepository $repository;

    /**
     * Constructor.
     *
     * @psalm-param class-string<T> $className the entity class name.
     * @psalm-suppress PropertyTypeCoercion
     */
    public function __construct(Generator $generator, EntityManagerInterface $manager, string $className)
    {
        parent::__construct($generator);
        $this->repository = $manager->getRepository($className);
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
            return $this->randomElement(\array_merge($this->distinctValues[$field], [null]));
        }

        return $this->randomElement($this->distinctValues[$field]);
    }

    /**
     * Gets a random entity.
     *
     * @psalm-return T|null
     */
    protected function entity()
    {
        /** @psalm-var T|null $entity */
        $entity = $this->randomElement($this->getEntities());

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
     * @psalm-return T[]
     */
    protected function getEntities(): array
    {
        if (empty($this->entities)) {
            $criteria = $this->getCriteria();
            $repository = $this->getRepository();
            $this->entities = empty($criteria) ? $repository->findAll() : $repository->findBy($criteria);
        }

        return $this->entities;
    }

    /**
     * @return AbstractRepository<T>
     */
    protected function getRepository(): AbstractRepository
    {
        return $this->repository;
    }
}
