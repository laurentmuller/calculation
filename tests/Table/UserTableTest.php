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
use App\Service\RoleService;
use App\Table\DataQuery;
use App\Table\UserTable;
use App\Tests\TranslatorMockTrait;
use App\Utils\FormatUtils;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Twig\Environment;
use Twig\Error\Error;

/**
 * @extends EntityTableTestCase<User, UserRepository, UserTable>
 */
final class UserTableTest extends EntityTableTestCase
{
    use TranslatorMockTrait;

    private const int TOKEN_DEFAULT = 0;
    private const int TOKEN_NO_USER = 1;
    private const int TOKEN_USER = 2;

    private int $state;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->state = self::TOKEN_DEFAULT;
    }

    public function testFormatEnabled(): void
    {
        $table = $this->createTableWithMock();

        $actual = $table->formatEnabled(true);
        self::assertSame('common.value_enabled', $actual);

        $actual = $table->formatEnabled(false);
        self::assertSame('common.value_disabled', $actual);
    }

    /**
     * @throws Error
     */
    public function testFormatImage(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->method('render')
            ->willReturnArgument(0);
        $table = $this->createTableWithMock(twig: $twig);

        $expected = 'macros/_cell_user_image.html.twig';
        $actual = $table->formatImage('image', []);
        self::assertSame($expected, $actual);
    }

    public function testFormatLastLogin(): void
    {
        $date = new DatePoint();
        $table = $this->createTableWithMock();

        $actual = $table->formatLastLogin(null);
        self::assertSame('common.value_none', $actual);

        $actual = $table->formatLastLogin($date);
        $expected = FormatUtils::formatDateTime($date);
        self::assertSame($expected, $actual);
    }

    public function testFormatRole(): void
    {
        $roleService = $this->createMock(RoleService::class);
        $roleService->method('getRoleIconAndName')
            ->willReturn('role');
        $table = $this->createTableWithMock(roleService: $roleService);

        $actual = $table->formatRole(null);
        self::assertSame('role', $actual);
    }

    public function testWithoutTokenNoUser(): void
    {
        $this->state = self::TOKEN_NO_USER;
        $this->processDataQuery(new DataQuery());
    }

    public function testWithTokenUser(): void
    {
        $this->state = self::TOKEN_USER;
        $this->processDataQuery(new DataQuery());
    }

    #[\Override]
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
            'lastLogin' => new DatePoint(),
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

    #[\Override]
    protected function createMockRepository(MockObject&QueryBuilder $queryBuilder): MockObject&UserRepository
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->method('getTableQueryBuilder')
            ->willReturn($queryBuilder);

        return $repository;
    }

    /**
     * @phpstan-param UserRepository $repository
     */
    #[\Override]
    protected function createTable(AbstractRepository $repository): UserTable
    {
        $translator = $this->createMockTranslator();
        $roleService = $this->createMock(RoleService::class);
        $twig = $this->createMock(Environment::class);
        $security = $this->createMockSecurity();

        return new UserTable($repository, $roleService, $translator, $twig, $security);
    }

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

    private function createTableWithMock(
        ?Environment $twig = null,
        ?RoleService $roleService = null,
    ): UserTable {
        return new UserTable(
            $this->createMock(UserRepository::class),
            $roleService ?? $this->createMock(RoleService::class),
            $this->createMockTranslator(),
            $twig ?? $this->createMock(Environment::class),
            $this->createMock(Security::class)
        );
    }
}
