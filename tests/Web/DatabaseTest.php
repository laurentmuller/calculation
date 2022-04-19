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
use App\Repository\PropertyRepository;
use App\Repository\UserRepository;
use App\Tests\DatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Unit test for database.
 *
 * @author Laurent Muller
 */
class DatabaseTest extends KernelTestCase
{
    use DatabaseTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        self::bootKernel();
    }

    /**
     * @return array<int, array<int, int|string>>
     */
    public function getRepositories(): array
    {
        return [
            [GroupRepository::class, 0],
            [CategoryRepository::class, 0],
            [GroupMarginRepository::class, 0],
            [ProductRepository::class, 0],
            [CalculationStateRepository::class, 0],
            [CalculationRepository::class, 0],
            [CalculationGroupRepository::class, 0],
            [CalculationItemRepository::class, 0],
            [GlobalMarginRepository::class, 0],
            [PropertyRepository::class, 0],
            [UserRepository::class, 4],
        ];
    }

    /**
     * @return array<int, array<int, int|string>>
     */
    public function getTables(): array
    {
        return [
            ['sy_Group', 0],
            ['sy_Category', 0],
            ['sy_GroupMargin', 0],
            ['sy_Product', 0],
            ['sy_CalculationState', 0],
            ['sy_Calculation', 0],
            ['sy_CalculationGroup', 0],
            ['sy_CalculationItem', 0],
            ['sy_GlobalMargin', 0],
            ['sy_Property', 0],
            ['sy_User', 4],
        ];
    }

    /**
     * @return array<int, array<int, int|string>>
     */
    public function getUsers(): array
    {
        return [
            [AbstractAuthenticateWebTestCase::ROLE_USER, RoleInterface::ROLE_USER],
            [AbstractAuthenticateWebTestCase::ROLE_ADMIN, RoleInterface::ROLE_ADMIN],
            [AbstractAuthenticateWebTestCase::ROLE_SUPER_ADMIN, RoleInterface::ROLE_SUPER_ADMIN],
            [AbstractAuthenticateWebTestCase::ROLE_DISABLED, RoleInterface::ROLE_USER],
        ];
    }

    /**
     * @dataProvider getRepositories
     *
     * @template T of \App\Entity\AbstractEntity
     *
     * @param class-string<T> $className
     */
    public function testRepository(string $className, int $expected): void
    {
        /**
         * @var AbstractRepository $repository
         * @psalm-var AbstractRepository<T> $repository
         */
        $repository = static::getContainer()->get($className);
        $this->assertNotNull($repository);

        $result = $repository->findAll();
        $this->assertCount($expected, $result);
    }

    /**
     * @dataProvider getTables
     */
    public function testTable(string $tablename, int $expected): void
    {
        $query = "SELECT COUNT(id) FROM $tablename";
        $result = self::$database->querySingle($query);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider getUsers
     */
    public function testUser(string $username, string $role): void
    {
        /** @var UserRepository $repository */
        $repository = static::getContainer()->get(UserRepository::class);
        $this->assertNotNull($repository);

        /** @var User $user */
        $user = $repository->findOneBy(['username' => $username]);
        $this->assertInstanceOf(User::class, $user);

        $this->assertEquals($username, $user->getUserIdentifier());
        $this->assertTrue($user->hasRole($role));
    }
}
