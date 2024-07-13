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
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends EntityTypeTestCase<User, UserType>
 */
class UserTypeTest extends EntityTypeTestCase
{
    use PasswordHasherExtensionTrait;
    use TranslatorMockTrait;
    use VichImageTypeTrait;

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

    protected function getEntityClass(): string
    {
        return User::class;
    }

    /**
     * @throws Exception|\ReflectionException
     */
    protected function getExtensions(): array
    {
        return \array_merge(parent::getExtensions(), [$this->getPasswordHasherExtension()]);
    }

    protected function getFormTypeClass(): string
    {
        return UserType::class;
    }

    /**
     * @throws Exception
     */
    protected function getPreloadedExtensions(): array
    {
        return [
            $this->createVichImageType(),
            new PlainType($this->createMockTranslator()),
            new RoleChoiceType($this->createMockSecurity()),
        ];
    }

    /**
     * @throws Exception
     */
    private function createMockSecurity(): MockObject&Security
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')
            ->willReturn(null);

        return $security;
    }
}
