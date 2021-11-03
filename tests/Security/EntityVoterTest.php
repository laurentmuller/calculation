<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
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

/**
 * Unit test for {@link App\Security\EntityVoter} class.
 *
 * @author Laurent Muller
 *
 * @see EntityVoter
 */
class EntityVoterTest extends TestCase implements EntityVoterInterface
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
        $expected = EntityVoter::ACCESS_ABSTAIN;
        $this->checkVote($user, $subject, $attribute, $expected);
    }

    public function testAbstainSubject(): void
    {
        $user = $this->getDefaultUser();
        $attribute = self::ATTRIBUTE_ADD;
        $subject = static::class;
        $expected = EntityVoter::ACCESS_ABSTAIN;
        $this->checkVote($user, $subject, $attribute, $expected);
    }

    public function testAdmin(): void
    {
        $role = EntityVoter::getRoleAdmin();
        $user = $this->getAdminUser()
            ->setRights($role->getRights())
            ->setOverwrite(true);

        $attribute = self::ATTRIBUTE_ADD;
        $subject = User::class;
        $expected = EntityVoter::ACCESS_GRANTED;
        $this->checkVote($user, $subject, $attribute, $expected);
    }

    public function testDisable(): void
    {
        $user = $this->getDisableUser();
        $attribute = self::ATTRIBUTE_ADD;
        $subject = Calculation::class;
        $expected = EntityVoter::ACCESS_DENIED;
        $this->checkVote($user, $subject, $attribute, $expected);
    }

    public function testEntities(): void
    {
        $entities = \array_keys(EntityVoter::ENTITY_OFFSETS);
        for ($i = 0; $i < \count($entities); ++$i) {
            $expected = $i;
            $name = $entities[$i];
            $actual = $this->voter->getEntityOffset($name);
            $this->assertEquals($expected, $actual);
        }
    }

    public function testMaskAttributes(): void
    {
        $keys = \array_keys(EntityVoter::MASK_ATTRIBUTES);
        for ($i = 0; $i < \count($keys); ++$i) {
            $expected = 2 ** $i;
            $name = $keys[$i];
            $actual = $this->voter->getAttributeMask($name);
            $this->assertEquals($expected, $actual);
        }
    }

    public function testSuperAdmin(): void
    {
        $user = $this->getSuperAdminUser();
        $attribute = self::ATTRIBUTE_ADD;
        $subject = Calculation::class;
        $expected = EntityVoter::ACCESS_GRANTED;
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
        return new UsernamePasswordToken($user, null, 'main', $user->getRoles());
    }
}
