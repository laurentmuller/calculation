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
use App\Interfaces\EntityVoterInterface;
use App\Interfaces\RoleInterface;
use App\Security\EntityVoter;
use App\Service\ApplicationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Unit test for {@link EntityVoter} class.
 *
 * @author Laurent Muller
 *
 * @see EntityVoter
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
        $attribute = EntityVoterInterface::ATTRIBUTE_ADD;
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

        $attribute = EntityVoterInterface::ATTRIBUTE_ADD;
        $subject = User::class;
        $expected = VoterInterface::ACCESS_GRANTED;
        $this->checkVote($user, $subject, $attribute, $expected);
    }

    public function testDisable(): void
    {
        $user = $this->getDisableUser();
        $attribute = EntityVoterInterface::ATTRIBUTE_ADD;
        $subject = Calculation::class;
        $expected = VoterInterface::ACCESS_DENIED;
        $this->checkVote($user, $subject, $attribute, $expected);
    }

    public function testEntities(): void
    {
        $entities = \array_keys(EntityVoter::ENTITY_OFFSETS);
        foreach ($entities as $index => $entity) {
            $actual = $this->voter->getEntityOffset($entity);
            $this->assertEquals($index, $actual);
        }
    }

    public function testMaskAttributes(): void
    {
        $keys = \array_keys(EntityVoter::MASK_ATTRIBUTES);
        foreach ($keys as $index => $key) {
            $expected = 2 ** $index;
            $actual = $this->voter->getAttributeMask($key);
            $this->assertEquals($expected, $actual);
        }
    }

    public function testSuperAdmin(): void
    {
        $user = $this->getSuperAdminUser();
        $attribute = EntityVoterInterface::ATTRIBUTE_ADD;
        $subject = Calculation::class;
        $expected = VoterInterface::ACCESS_GRANTED;
        $this->checkVote($user, $subject, $attribute, $expected);
    }

    protected function echo(string $name, string $value, bool $newLine = false): void
    {
        $format = "\n%-15s: %s" . ($newLine ? "\n" : '');
        \printf($format, \htmlspecialchars($name), $value);
    }

    /**
     * @param mixed $subject
     * @param mixed $attribute
     * @param mixed $expected
     */
    private function checkVote(User $user, $subject, $attribute, $expected): void
    {
        $token = $this->getUserToken($user);
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
