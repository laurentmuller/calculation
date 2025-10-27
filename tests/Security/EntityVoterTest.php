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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class EntityVoterTest extends TestCase
{
    private MockObject&ApplicationService $application;
    private RoleBuilderService $builder;
    private EntityVoter $voter;

    #[\Override]
    protected function setUp(): void
    {
        $this->builder = new RoleBuilderService();
        $adminRights = $this->builder->getRoleAdmin()->getRights();
        $userRights = $this->builder->getRoleUser()->getRights();

        $this->application = $this->createMock(ApplicationService::class);
        $this->application->method('getAdminRights')
            ->willReturn($adminRights);
        $this->application->method('getUserRights')
            ->willReturn($userRights);

        $this->voter = new EntityVoter($this->application);
    }

    /**
     * @phpstan-return \Generator<int, array{string, bool}>
     */
    public static function getSupportsAttribute(): \Generator
    {
        yield ['add', true];
        yield ['ADD', true];
        yield ['Fake', false];
        yield ['', false];
    }

    public function testAbstainAttribute(): void
    {
        $user = $this->getDefaultUser();
        $subject = Calculation::class;
        $attribute = 'FakeAttribute';
        $expected = VoterInterface::ACCESS_ABSTAIN;
        $this->assertVote($user, $subject, $attribute, $expected);
    }

    public function testAbstainSubject(): void
    {
        $user = $this->getDefaultUser();
        $subject = self::class;
        $attribute = EntityPermission::ADD;
        $expected = VoterInterface::ACCESS_ABSTAIN;
        $this->assertVote($user, $subject, $attribute, $expected);
    }

    public function testAdmin(): void
    {
        $role = $this->builder->getRoleAdmin();
        $user = $this->getAdminUser()
            ->setRights($role->getRights());
        $subject = User::class;
        $attribute = EntityPermission::ADD;
        $expected = VoterInterface::ACCESS_GRANTED;
        $this->assertVote($user, $subject, $attribute, $expected);
    }

    public function testAdminOverwrite(): void
    {
        $role = $this->builder->getRoleAdmin();
        $user = $this->getAdminUser()
            ->setRights($role->getRights())
            ->setOverwrite(true);
        $subject = User::class;
        $attribute = EntityPermission::ADD;
        $expected = VoterInterface::ACCESS_GRANTED;
        $this->assertVote($user, $subject, $attribute, $expected);
    }

    public function testDisable(): void
    {
        $user = $this->getDisableUser();
        $subject = Calculation::class;
        $attribute = EntityPermission::ADD;
        $expected = VoterInterface::ACCESS_DENIED;
        $this->assertVote($user, $subject, $attribute, $expected);
    }

    public function testInvalidAttribute(): void
    {
        $user = $this->getAdminUser();
        $subject = EntityName::CALCULATION;
        $attribute = 'fake';
        $expected = VoterInterface::ACCESS_ABSTAIN;
        $this->assertVote($user, $subject, $attribute, $expected);
    }

    public function testInvalidSubject(): void
    {
        $user = $this->getAdminUser();
        $subject = 'fake';
        $attribute = EntityPermission::ADD;
        $expected = VoterInterface::ACCESS_ABSTAIN;
        $this->assertVote($user, $subject, $attribute, $expected);
    }

    public function testNotUserInstance(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $subject = EntityName::PRODUCT;
        $attribute = EntityPermission::LIST;
        $actual = $this->voter->vote($token, $subject, [$attribute], new Vote());
        self::assertSame(VoterInterface::ACCESS_DENIED, $actual);
    }

    public function testSuperAdmin(): void
    {
        $user = $this->getSuperAdminUser();
        $subject = Calculation::class;
        $attribute = EntityPermission::ADD;
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
        $role = $this->builder->getRoleUser();
        $user = $this->getDefaultUser()
            ->setRights($role->getRights());

        $subject = Calculation::class;
        $attribute = EntityPermission::ADD;
        $expected = VoterInterface::ACCESS_GRANTED;
        $this->assertVote($user, $subject, $attribute, $expected);
    }

    public function testUserDenied(): void
    {
        $user = $this->getDefaultUser();
        $subject = User::class;
        $attribute = EntityPermission::ADD;
        $expected = VoterInterface::ACCESS_DENIED;
        $this->assertVote($user, $subject, $attribute, $expected);
    }

    public function testVoteOnAttribute(): void
    {
        $voter = new class($this->application) extends EntityVoter {
            #[\Override]
            public function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
            {
                return parent::voteOnAttribute($attribute, $subject, $token, $vote ?? new Vote());
            }
        };

        $role = $this->builder->getRoleUser();
        $user = $this->getDefaultUser()
            ->setRights($role->getRights());
        $token = $this->getUserToken($user);

        $args = [
            'fake',
            EntityName::CALCULATION->name,
            $token,
        ];
        $actual = $voter->voteOnAttribute(...$args);
        self::assertFalse($actual);

        $args = [
            EntityPermission::ADD->name,
            'fake',
            $token,
        ];
        $actual = $voter->voteOnAttribute(...$args);
        self::assertFalse($actual);
    }

    /**
     * @phpstan-param VoterInterface::ACCESS_* $expected
     */
    private function assertVote(User $user, mixed $subject, mixed $attribute, int $expected): void
    {
        $token = $this->getUserToken($user);
        $actual = $this->voter->vote($token, $subject, [$attribute], new Vote());
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
     * @phpstan-param RoleInterface::ROLE_* $role
     */
    private function getUser(string $role): User
    {
        $user = new User();

        return $user->setUsername($role)
            ->setRole($role);
    }

    private function getUserToken(User $user): UsernamePasswordToken
    {
        return new UsernamePasswordToken($user, 'main', $user->getRoles());
    }
}
