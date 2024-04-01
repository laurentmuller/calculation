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
use App\Tests\Data\Database;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Trait to manage database test.
 */
trait DatabaseTrait
{
    /**
     * The database.
     */
    protected static ?Database $database = null;

    public static function setUpBeforeClass(): void
    {
        self::$database = Database::createDatabase();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$database instanceof Database) {
            self::$database->close();
            self::$database = Database::deleteDatabase();
            self::$database = null;
        }
    }

    /**
     * Adds an entity to the database.
     *
     * @template T of EntityInterface
     *
     * @psalm-param T|null $entity
     *
     * @psalm-return ($entity is null ? null : T)
     *
     * @throws ORMException
     */
    protected function addEntity(?EntityInterface $entity): ?EntityInterface
    {
        if ($entity instanceof EntityInterface) {
            $em = $this->getManager();
            $em->persist($entity);
            $em->flush();

            return $entity;
        }

        return null;
    }

    /**
     * Delete an entity from the database.
     *
     * @return null this function returns always null
     *
     * @throws ORMException
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
    protected function getManager(): EntityManager
    {
        /** @var ManagerRegistry $registry */
        $registry = static::getContainer()->get('doctrine');

        /** @var EntityManager $manager */
        $manager = $registry->getManager();

        return $manager;
    }
}
