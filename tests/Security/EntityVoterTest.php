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

namespace App\Tests\Security;

use App\Entity\Calculation;
use App\Entity\User;
use App\Enums\EntityName;
use App\Enums\EntityPermission;
use App\Interfaces\RoleInterface;
use App\Security\EntityVoter;
use App\Service\ApplicationService;
use App\Service\RoleBuilderService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[CoversClass(EntityVoter::class)]
class EntityVoterTest extends TestCase
{
    private EntityVoter $voter;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $builder = new RoleBuilderService();
        $service = $this->createMock(ApplicationService::class);
        $service->method('getAdminRights')
            ->willReturn($builder->getRoleAdmin()->getRights());
        $service->method('getUserRights')
            ->willReturn($builder->getRoleUser()->getRights());

        $this->voter = new EntityVoter($service);
    }

    public static function getSupportsAttribute(): \Iterator
    {
        yield ['add', true];
        yield ['ADD', true];
        yield ['Fake', false];
        yield ['', false];
    }

    public function testAbstainAttribute(): void
    {
        $user = $this->getDefaultUser();
        $attribute = 'FakeAttribute';
        $subject = Calculation::class;
        $expected = VoterInterface::ACCESS_ABSTAIN;
        $this->assertVote($user, $subject, $attribute, $expected);
    }

    public function testAbstainSubject(): void
    {
        $user = $this->getDefaultUser();
        $attribute = 'ADD';
        $subject = static::class;
        $expected = VoterInterface::ACCESS_ABSTAIN;
        $this->assertVote($user, $subject, $attribute, $expected);
    }

    public function testAdmin(): void
    {
        $builder = new RoleBuilderService();
        $role = $builder->getRoleAdmin();
        $user = $this->getAdminUser()
            ->setRights($role->getRights());

        $attribute = 'ADD';
        $subject = User::class;
        $expected = VoterInterface::ACCESS_GRANTED;
        $this->assertVote($user, $subject, $attribute, $expected);
    }

    public function testAdminOverwrite(): void
    {
        $builder = new RoleBuilderService();
        $role = $builder->getRoleAdmin();
        $user = $this->getAdminUser()
            ->setRights($role->getRights())
            ->setOverwrite(true);

        $attribute = 'ADD';
        $subject = User::class;
        $expected = VoterInterface::ACCESS_GRANTED;
        $this->assertVote($user, $subject, $attribute, $expected);
    }

    public function testDisable(): void
    {
        $user = $this->getDisableUser();
        $attribute = 'ADD';
        $subject = Calculation::class;
        $expected = VoterInterface::ACCESS_DENIED;
        $this->assertVote($user, $subject, $attribute, $expected);
    }

    public function testEntityName(): void
    {
        $user = $this->getAdminUser();
        $expected = VoterInterface::ACCESS_ABSTAIN;
        $this->assertVote($user, EntityName::CALCULATION, 'fake', $expected);
    }

    public function testEntityPermission(): void
    {
        $user = $this->getAdminUser();
        $expected = VoterInterface::ACCESS_ABSTAIN;
        $this->assertVote($user, 'fake', EntityPermission::ADD, $expected);
    }

    public function testSuperAdmin(): void
    {
        $user = $this->getSuperAdminUser();
        $attribute = 'ADD';
        $subject = Calculation::class;
        $expected = VoterInterface::ACCESS_GRANTED;
        $this->assertVote($user, $subject, $attribute, $expected);
    }

    #[DataProvider('getSupportsAttribute')]
    public function testSupportsAttribute(string $value, bool $expected): void
    {
        $actual = $this->voter->supportsAttribute($value);
        self::assertSame($expected, $actual);
    }

    public function testUser(): void
    {
        $builder = new RoleBuilderService();
        $role = $builder->getRoleUser();
        $user = $this->getDefaultUser()
            ->setRights($role->getRights());

        $attribute = 'ADD';
        $subject = Calculation::class;
        $expected = VoterInterface::ACCESS_GRANTED;
        $this->assertVote($user, $subject, $attribute, $expected);
    }

    /**
     * @throws \ReflectionException
     *
     * @psalm-suppress UnusedMethodCall
     */
    public function testVoteOnAttribute(): void
    {
        $class = new \ReflectionClass($this->voter);
        $method = $class->getMethod('voteOnAttribute');
        $method->setAccessible(true);

        $builder = new RoleBuilderService();
        $role = $builder->getRoleUser();
        $user = $this->getDefaultUser()
            ->setRights($role->getRights());
        $token = $this->getUserToken($user);

        $args = [
            'fake',
            EntityName::CALCULATION->name,
            $token,
        ];
        /** @psalm-var bool $actual */
        $actual = $method->invoke($this->voter, ...$args);
        self::assertFalse($actual);

        $args = [
            EntityPermission::ADD->name,
            'fake',
            $token,
        ];
        /** @psalm-var bool $actual */
        $actual = $method->invoke($this->voter, ...$args);
        self::assertFalse($actual);
    }

    private function assertVote(User $user, mixed $subject, mixed $attribute, mixed $expected): void
    {
        $token = $this->getUserToken($user);
        $actual = $this->voter->vote($token, $subject, [$attribute]);
        self::assertSame($expected, $actual);
    }

    private function getAdminUser(): User
    {
        return $this->getUser(RoleInterface::ROLE_ADMIN);
    }

    private function getDefaultUser(): User
    {
        return $this->getUser(RoleInterface::ROLE_USER);
    }

    private function getDisableUser(): User
    {
        return $this->getDefaultUser()->setEnabled(false);
    }

    private function getSuperAdminUser(): User
    {
        return $this->getUser(RoleInterface::ROLE_SUPER_ADMIN);
    }

    /**
     * @psalm-param RoleInterface::ROLE_* $role
     */
    private function getUser(string $role): User
    {
        $user = new User();
        $user->setRole($role);

        return $user;
    }

    private function getUserToken(User $user): UsernamePasswordToken
    {
        return new UsernamePasswordToken($user, 'main', $user->getRoles());
    }
}
