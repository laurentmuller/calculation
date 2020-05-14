<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\Calculation;
use App\Entity\User;
use App\Interfaces\EntityVoterInterface;
use App\Security\EntityVoter;
use App\Service\ApplicationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Unit test for EntityVoter class.
 *
 * @author Laurent Muller
 *
 * @see EntityVoter
 */
class EntityVoterTest extends TestCase implements EntityVoterInterface
{
    /**
     * @var EntityVoter
     */
    private $voter;

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

    public function testSuperAdmin(): void
    {
        $user = $this->getSuperAdminUser();
        $attribute = self::ATTRIBUTE_ADD;
        $subject = Calculation::class;
        $expected = EntityVoter::ACCESS_GRANTED;

        $this->checkVote($user, $subject, $attribute, $expected);
    }

    private function checkVote(User $user, $subject, $attribute, $expected): void
    {
        $token = $this->getUserToken($user);
        $result = $this->voter->vote($token, $subject, [$attribute]);

        $this->assertSame($expected, $result);
    }

    private function getAdminUser(): User
    {
        return $this->getUser(User::ROLE_ADMIN);
    }

    private function getDefaultUser(): User
    {
        return $this->getUser(User::ROLE_DEFAULT);
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
        return $this->getUser(User::ROLE_SUPER_ADMIN);
    }

    private function getUser(string $role): User
    {
        $user = new User();
        $user->addRole($role);

        return $user;
    }

    private function getUserToken(User $user): UsernamePasswordToken
    {
        return new UsernamePasswordToken($user, null, 'main', $user->getRoles());
    }
}
