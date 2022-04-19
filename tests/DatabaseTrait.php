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

use App\Tests\Data\Database;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Trait to manage database test.
 *
 * @author Laurent Muller
 */
trait DatabaseTrait
{
    /**
     * The database.
     *
     * @var ?Database
     */
    protected static $database;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        self::$database = Database::createDatabase();
    }

    /**
     * {@inheritdoc}
     */
    public static function tearDownAfterClass(): void
    {
        if (null !== self::$database) {
            self::$database->close();
            self::$database = Database::deleteDatabase();
        }
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
