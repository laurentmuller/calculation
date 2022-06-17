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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Unit test for {@link EntityVoter} class.
 */
class EntityVoterTest extends TestCase
{
    private ?EntityVoter $voter = null;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->voter = $this->getEntityVoter();
    }

    public function testAbstainAttribute(): void
    {
        $user = $this->getDefaultUser();
        $attribute = 'FakeAttribute';
        $subject = Calculation::class;
        $expected = VoterInterface::ACCESS_ABSTAIN;
        $this->checkVote($user, $subject, $attribute, $expected);
    }

    public function testAbstainSubject(): void
    {
        $user = $this->getDefaultUser();
        $attribute = 'ADD';
        $subject = static::class;
        $expected = VoterInterface::ACCESS_ABSTAIN;
        $this->checkVote($user, $subject, $attribute, $expected);
    }

    public function testAdmin(): void
    {
        $role = EntityVoter::getRoleAdmin();
        $user = $this->getAdminUser()
            ->setRights($role->getRights())
            ->setOverwrite(true);

        $attribute = 'ADD';
        $subject = User::class;
        $expected = VoterInterface::ACCESS_GRANTED;
        $this->checkVote($user, $subject, $attribute, $expected);
    }

    public function testDisable(): void
    {
        $user = $this->getDisableUser();
        $attribute = 'ADD';
        $subject = Calculation::class;
        $expected = VoterInterface::ACCESS_DENIED;
        $this->checkVote($user, $subject, $attribute, $expected);
    }

    public function testEntities(): void
    {
        $entities = \array_values(EntityName::constants());
        foreach ($entities as $index => $entity) {
            $this->assertNotNull($this->voter);
            $actual = $this->voter->getEntityOffset($entity);
            $this->assertEquals($index, $actual);
        }
    }

    public function testMaskAttributes(): void
    {
        $keys = \array_values(EntityPermission::constants());
        foreach ($keys as $index => $key) {
            $expected = 2 ** $index;
            $this->assertNotNull($this->voter);
            $actual = $this->voter->getPermissionValue($key);
            $this->assertEquals($expected, $actual);
        }
    }

    public function testSuperAdmin(): void
    {
        $user = $this->getSuperAdminUser();
        $attribute = 'ADD';
        $subject = Calculation::class;
        $expected = VoterInterface::ACCESS_GRANTED;
        $this->checkVote($user, $subject, $attribute, $expected);
    }

    private function checkVote(User $user, mixed $subject, mixed $attribute, mixed $expected): void
    {
        $token = $this->getUserToken($user);
        $this->assertNotNull($this->voter);
        $result = $this->voter->vote($token, $subject, [$attribute]);
        $this->assertEquals($expected, $result);
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

    private function getEntityVoter(): EntityVoter
    {
        return new EntityVoter($this->createMock(ApplicationService::class));
    }

    private function getSuperAdminUser(): User
    {
        return $this->getUser(RoleInterface::ROLE_SUPER_ADMIN);
    }

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
