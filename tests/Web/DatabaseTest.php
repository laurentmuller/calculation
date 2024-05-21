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
use App\Repository\CalculationGroupRepository;
use App\Repository\CalculationItemRepository;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Repository\CategoryRepository;
use App\Repository\GlobalMarginRepository;
use App\Repository\GroupMarginRepository;
use App\Repository\GroupRepository;
use App\Repository\ProductRepository;
use App\Repository\UserPropertyRepository;
use App\Repository\UserRepository;
use App\Tests\Data\Database;
use App\Tests\DatabaseTrait;
use App\Tests\ServiceTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(Database::class)]
class DatabaseTest extends KernelTestCase
{
    use DatabaseTrait;
    use ServiceTrait;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public static function getRepositories(): \Iterator
    {
        yield [GroupRepository::class, 0];
        yield [CategoryRepository::class, 0];
        yield [GroupMarginRepository::class, 0];
        yield [ProductRepository::class, 0];
        yield [CalculationStateRepository::class, 0];
        yield [CalculationRepository::class, 0];
        yield [CalculationGroupRepository::class, 0];
        yield [CalculationItemRepository::class, 0];
        yield [GlobalMarginRepository::class, 0];
        yield [UserPropertyRepository::class, 0];
        yield [UserRepository::class, 4];
    }

    public static function getTables(): \Iterator
    {
        yield ['sy_Group', 0];
        yield ['sy_Category', 0];
        yield ['sy_GroupMargin', 0];
        yield ['sy_Product', 0];
        yield ['sy_CalculationState', 0];
        yield ['sy_Calculation', 0];
        yield ['sy_CalculationGroup', 0];
        yield ['sy_CalculationItem', 0];
        yield ['sy_GlobalMargin', 0];
        yield ['sy_Property', 1];
        yield ['sy_User', 4];
    }

    public static function getUsers(): \Iterator
    {
        yield [AbstractAuthenticateWebTestCase::ROLE_USER, RoleInterface::ROLE_USER];
        yield [AbstractAuthenticateWebTestCase::ROLE_ADMIN, RoleInterface::ROLE_ADMIN];
        yield [AbstractAuthenticateWebTestCase::ROLE_SUPER_ADMIN, RoleInterface::ROLE_SUPER_ADMIN];
        yield [AbstractAuthenticateWebTestCase::ROLE_DISABLED, RoleInterface::ROLE_USER];
    }

    /**
     * @template TEntity of EntityInterface
     *
     * @param class-string<TEntity> $className
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getRepositories')]
    public function testRepository(string $className, int $expected): void
    {
        /**
         * @var AbstractRepository $repository
         *
         * @psalm-var AbstractRepository<TEntity> $repository
         */
        $repository = $this->getService($className);
        $result = $repository->findAll();
        self::assertCount($expected, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getTables')]
    public function testTable(string $tableName, int $expected): void
    {
        $database = self::$database;
        self::assertNotNull($database);
        $query = "SELECT COUNT(id) FROM $tableName";
        $result = $database->querySingle($query);
        self::assertSame($expected, $result);
    }

    /**
     * @psalm-param RoleInterface::ROLE_* $role
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getUsers')]
    public function testUser(string $username, string $role): void
    {
        $repository = $this->getService(UserRepository::class);
        $user = $repository->findOneBy(['username' => $username]);
        self::assertInstanceOf(User::class, $user);
        self::assertSame($username, $user->getUserIdentifier());
        self::assertTrue($user->hasRole($role));
    }
}
