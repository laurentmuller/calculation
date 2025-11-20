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

namespace App\Tests;

use App\Interfaces\EntityInterface;
use App\Tests\Fixture\FixtureDatabase;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * Trait to manage database test.
 */
trait DatabaseTrait
{
    /**
     * The database.
     */
    protected static ?FixtureDatabase $database = null;

    public static function setUpBeforeClass(): void
    {
        self::$database = FixtureDatabase::createDatabase();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$database instanceof FixtureDatabase) {
            self::$database->close();
            self::$database = FixtureDatabase::deleteDatabase();
        }
    }

    /**
     * Adds an entity to the database.
     *
     * @template T of EntityInterface
     *
     * @phpstan-param T $entity
     *
     * @phpstan-return T
     */
    protected function addEntity(EntityInterface $entity): EntityInterface
    {
        $em = $this->getManager();
        $em->persist($entity);
        $em->flush();

        return $entity;
    }

    /**
     * Delete all entities for the given class.
     *
     * @phpstan-param class-string $className
     */
    protected function deleteEntitiesByClass(string $className): void
    {
        $manager = $this->getManager();
        $entities = $manager->getRepository($className)
            ->findAll();
        if ([] === $entities) {
            return;
        }

        foreach ($entities as $entity) {
            $manager->remove($entity);
        }
        $manager->flush();
    }

    /**
     * Delete an entity from the database.
     *
     * @return null this function returns always null
     */
    protected function deleteEntity(?EntityInterface $entity): null
    {
        if ($entity instanceof EntityInterface) {
            $em = $this->getManager();
            $em->remove($entity);
            $em->flush();
        }

        return null;
    }

    /**
     * Gets the entity manager.
     */
    protected function getManager(): ObjectManager
    {
        /** @phpstan-var ManagerRegistry $registry */
        $registry = static::getContainer()->get('doctrine');

        return $registry->getManager();
    }
}
