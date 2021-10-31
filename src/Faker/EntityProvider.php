<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
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
 * @template T of \App\Entity\AbstractEntity
 */
class EntityProvider extends Base
{
    /**
     * The entities.
     *
     * @psalm-var T[]
     */
    private ?array $entities = null;

    /**
     * @var AbstractRepository<T>
     */
    private AbstractRepository $repository;

    /**
     * @psalm-param class-string<T> $entityClass
     */
    public function __construct(Generator $generator, EntityManagerInterface $manager, string $entityClass)
    {
        parent::__construct($generator);
        $this->repository = $manager->getRepository($entityClass);
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
     * @param string $field the field name (column) to get values for
     * @param string $value a value to search within the column or <code>null</code> for all
     * @param int    $limit the maximum number of results to retrieve (the "limit") or <code>-1</code> for all
     *
     * @return mixed|null a random value or null if none
     */
    protected function distinctValue(string $field, ?string $value = null, int $limit = -1)
    {
        $repository = $this->getRepository();
        $values = $repository->getDistinctValues($field, $value, $limit);

        return $this->randomElement($values);
    }

    /**
     * Gets a random entity.
     *
     * @psalm-return T|null
     */
    protected function entity()
    {
        return $this->randomElement($this->getEntities());
    }

    /**
     * Find all entities.

     *
     * @psalm-return T[]
     */
    protected function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Gets all entities.
     *
     * @psalm-return T[]
     */
    protected function getEntities(): array
    {
        if (null === $this->entities) {
            $this->entities = $this->findAll();
        }

        return $this->entities;
    }

    /**
     * Gets the repository.
     *
     * @psalm-return AbstractRepository<T>
     */
    protected function getRepository(): AbstractRepository
    {
        return $this->repository;
    }
}
