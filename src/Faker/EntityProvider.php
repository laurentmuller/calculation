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
use Faker\Provider\Base;

/**
 * Entity provider.
 *
 * @template TEntity of EntityInterface
 *
 * @property \Faker\UniqueGenerator $unique
 */
class EntityProvider extends Base implements \Countable
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
     * @var list<TEntity>
     */
    private array $entities = [];

    /**
     * @param AbstractRepository<TEntity> $repository the repository to get entities from
     */
    public function __construct(Generator $generator, protected readonly AbstractRepository $repository)
    {
        parent::__construct($generator);
    }

    /**
     * Gets the number of entities.
     */
    #[\Override]
    public function count(): int
    {
        return \count($this->getEntities());
    }

    /**
     * Gets a random value for the given column.
     *
     * @param string $field     the field name (column) to get values for
     * @param bool   $allowNull true to allow null value
     *
     * @return mixed|null a random value or null if none
     */
    protected function distinctValue(string $field, bool $allowNull = false): mixed
    {
        // already loaded?
        if (!\array_key_exists($field, $this->distinctValues) || [] === $this->distinctValues[$field]) {
            $this->distinctValues[$field] = $this->repository->getDistinctValues($field);
        }

        $values = $this->distinctValues[$field];
        if ($allowNull) {
            $values[] = null;
        }

        return static::randomElement($values);
    }

    /**
     * Gets a random entity.
     *
     * @return TEntity|null
     */
    protected function entity()
    {
        /** @psalm-var TEntity|null */
        return static::randomElement($this->getEntities());
    }

    /**
     * Gets the criteria used to filter entities.
     *
     * @return array<string, mixed>
     */
    protected function getCriteria(): array
    {
        return [];
    }

    /**
     * Gets all entities.
     *
     * @return list<TEntity>
     */
    protected function getEntities(): array
    {
        if ([] === $this->entities) {
            $criteria = $this->getCriteria();
            $this->entities = [] === $criteria ? $this->repository->findAll() : $this->repository->findBy($criteria);
        }

        return $this->entities;
    }
}
