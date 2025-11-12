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

namespace App\Tests\Web;

use App\Entity\User;
use App\Interfaces\EntityInterface;
use App\Interfaces\RoleInterface;
use App\Repository\AbstractRepository;
use App\Repository\CalculationCategoryRepository;
use App\Repository\CalculationGroupRepository;
use App\Repository\CalculationItemRepository;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Repository\CategoryRepository;
use App\Repository\CustomerRepository;
use App\Repository\GlobalMarginRepository;
use App\Repository\GlobalPropertyRepository;
use App\Repository\GroupMarginRepository;
use App\Repository\GroupRepository;
use App\Repository\ProductRepository;
use App\Repository\TaskItemMarginRepository;
use App\Repository\TaskItemRepository;
use App\Repository\TaskRepository;
use App\Repository\UserPropertyRepository;
use App\Repository\UserRepository;
use App\Tests\DatabaseTrait;
use App\Tests\KernelServiceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class DatabaseTest extends KernelServiceTestCase
{
    use DatabaseTrait;

    private const PROPERTIES_COUNT = 1;
    private const USERS_COUNT = 4;

    public static function getRepositories(): \Generator
    {
        yield [CalculationCategoryRepository::class];
        yield [CalculationGroupRepository::class];
        yield [CalculationItemRepository::class];
        yield [CalculationRepository::class];
        yield [CalculationStateRepository::class];
        yield [CategoryRepository::class];
        yield [CustomerRepository::class];
        yield [GlobalMarginRepository::class];
        yield [GlobalPropertyRepository::class, self::PROPERTIES_COUNT];
        yield [GroupMarginRepository::class];
        yield [GroupRepository::class];
        yield [ProductRepository::class];
        yield [TaskItemMarginRepository::class];
        yield [TaskItemRepository::class];
        yield [TaskRepository::class];
        yield [UserPropertyRepository::class];
        yield [UserRepository::class, self::USERS_COUNT];
    }

    public static function getTables(): \Generator
    {
        yield ['sy_CalculationCategory'];
        yield ['sy_CalculationGroup'];
        yield ['sy_CalculationItem'];
        yield ['sy_Calculation'];
        yield ['sy_CalculationState'];
        yield ['sy_Category'];
        yield ['sy_Customer'];
        yield ['sy_GlobalMargin'];
        yield ['sy_Property', self::PROPERTIES_COUNT]; // GlobalProperty
        yield ['sy_GroupMargin'];
        yield ['sy_Group'];
        yield ['sy_Product'];
        yield ['sy_TaskItemMargin'];
        yield ['sy_TaskItem'];
        yield ['sy_Task'];
        yield ['sy_UserProperty'];
        yield ['sy_User', self::USERS_COUNT];
    }

    public static function getUsers(): \Generator
    {
        yield [AuthenticateWebTestCase::ROLE_USER, RoleInterface::ROLE_USER];
        yield [AuthenticateWebTestCase::ROLE_ADMIN, RoleInterface::ROLE_ADMIN];
        yield [AuthenticateWebTestCase::ROLE_SUPER_ADMIN, RoleInterface::ROLE_SUPER_ADMIN];
        yield [AuthenticateWebTestCase::ROLE_DISABLED, RoleInterface::ROLE_USER];
    }

    /**
     * @template TEntity of EntityInterface
     * @template TRepository of AbstractRepository<TEntity>
     *
     * @param class-string<TRepository> $className
     */
    #[DataProvider('getRepositories')]
    public function testRepository(string $className, int $expected = 0): void
    {
        /** @var TRepository $repository */
        $repository = $this->getService($className);
        $result = $repository->findAll();
        self::assertCount($expected, $result);
    }

    #[DataProvider('getTables')]
    public function testTable(string $tableName, int $expected = 0): void
    {
        $database = self::$database;
        self::assertNotNull($database);
        $query = "SELECT COUNT(id) FROM $tableName";
        $result = $database->querySingle($query);
        self::assertSame($expected, $result);
    }

    /**
     * @phpstan-param RoleInterface::ROLE_* $role
     */
    #[DataProvider('getUsers')]
    public function testUser(string $username, string $role): void
    {
        $repository = $this->getService(UserRepository::class);
        $user = $repository->findOneBy(['username' => $username]);
        self::assertInstanceOf(User::class, $user);
        self::assertSame($username, $user->getUserIdentifier());
        self::assertTrue($user->hasRole($role));
    }
}
