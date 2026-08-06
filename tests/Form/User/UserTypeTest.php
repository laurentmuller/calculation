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

namespace App\Tests\Form\User;

use App\Entity\User;
use App\Form\Type\PlainType;
use App\Form\User\RoleChoiceType;
use App\Form\User\UserType;
use App\Interfaces\RoleInterface;
use App\Tests\Form\EntityTypeTestCase;
use App\Tests\TranslatorStubTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends EntityTypeTestCase<User, UserType>
 */
#[AllowMockObjectsWithoutExpectations]
final class UserTypeTest extends EntityTypeTestCase
{
    use PasswordHasherExtensionTrait;
    use TranslatorStubTrait;
    use VichImageTypeTrait;

    #[\Override]
    protected function getData(): array
    {
        return [
            'username' => 'username',
            'email' => 'email@email.com',
            'role' => RoleInterface::ROLE_USER,
            'enabled' => true,
            'imageFile' => null,
        ];
    }

    #[\Override]
    protected function getEntityClass(): string
    {
        return User::class;
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return \array_merge(parent::getExtensions(), [$this->getPasswordHasherExtension()]);
    }

    #[\Override]
    protected function getFormTypeClass(): string
    {
        return UserType::class;
    }

    #[\Override]
    protected function getPreloadedExtensions(): array
    {
        return [
            $this->createVichImageType(),
            new PlainType($this->createStubTranslator()),
            new RoleChoiceType($this->createMockSecurity()),
        ];
    }

    private function createMockSecurity(): Security
    {
        $security = self::createStub(Security::class);
        $security->method('getUser')
            ->willReturn(null);

        return $security;
    }
}
