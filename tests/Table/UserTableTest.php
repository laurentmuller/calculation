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

namespace App\Tests\Table;

use App\Entity\User;
use App\Interfaces\RoleInterface;
use App\Repository\AbstractRepository;
use App\Repository\UserRepository;
use App\Table\AbstractEntityTable;
use App\Table\AbstractTable;
use App\Table\DataQuery;
use App\Table\UserTable;
use App\Tests\TranslatorMockTrait;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Twig\Environment;

/**
 * @extends EntityTableTestCase<User, UserRepository, UserTable>
 */
#[CoversClass(AbstractTable::class)]
#[CoversClass(AbstractEntityTable::class)]
#[CoversClass(UserTable::class)]
class UserTableTest extends EntityTableTestCase
{
    use TranslatorMockTrait;

    private const TOKEN_DEFAULT = 0;
    private const TOKEN_NO_USER = 1;
    private const TOKEN_USER = 2;

    private int $state;

    protected function setUp(): void
    {
        parent::setUp();
        $this->state = self::TOKEN_DEFAULT;
    }

    /**
     * @throws Exception|\ReflectionException
     */
    public function testWithoutTokenNoUser(): void
    {
        $this->state = self::TOKEN_NO_USER;
        $this->processDataQuery(new DataQuery());
    }

    /**
     * @throws Exception|\ReflectionException
     */
    public function testWithTokenUser(): void
    {
        $this->state = self::TOKEN_USER;
        $this->processDataQuery(new DataQuery());
    }

    protected function createEntities(): array
    {
        $user = [
            'id' => 1,
            'image' => 'image1',
            'imageName' => 'imageName1',
            'username' => 'username1',
            'email' => 'email1',
            'role' => RoleInterface::ROLE_USER,
            'enabled' => true,
            'lastLogin' => new \DateTime(),
            'hashedToken' => 'hashedToken1',
        ];
        $admin = [
            'id' => 2,
            'image' => null,
            'imageName' => null,
            'username' => 'username2',
            'email' => 'email2',
            'role' => RoleInterface::ROLE_ADMIN,
            'enabled' => true,
            'lastLogin' => null,
            'hashedToken' => 'hashedToken1',
        ];

        return [$user, $admin];
    }

    protected function createRepository(MockObject&QueryBuilder $queryBuilder): MockObject&UserRepository
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->method('getTableQueryBuilder')
            ->willReturn($queryBuilder);

        return $repository;
    }

    /**
     * @psalm-param UserRepository $repository
     *
     * @throws Exception
     */
    protected function createTable(AbstractRepository $repository): UserTable
    {
        $translator = $this->createMockTranslator();
        $twig = $this->createMock(Environment::class);
        $security = $this->createMockSecurity();

        return new UserTable($repository, $translator, $twig, $security);
    }

    /**
     * @throws Exception
     */
    private function createMockSecurity(): MockObject&Security
    {
        $security = $this->createMock(Security::class);
        if (self::TOKEN_DEFAULT === $this->state) {
            return $security;
        }

        $user = null;
        if (self::TOKEN_USER === $this->state) {
            $user = new User();
        }

        $originalToken = $this->createMock(TokenInterface::class);
        $originalToken->method('getUser')
            ->willReturn($user);

        $token = $this->createMock(SwitchUserToken::class);
        $token->method('getOriginalToken')
            ->willReturn($originalToken);

        $security->method('getToken')
            ->willReturn($token);

        return $security;
    }
}
