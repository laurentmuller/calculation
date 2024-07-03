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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\PreloadedExtension;

/**
 * @extends EntityTypeTestCase<User, UserType>
 */
#[CoversClass(UserType::class)]
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
     * @throws Exception
     */
    protected function getExtensions(): array
    {
        /** @psalm-var array $extensions */
        $extensions = parent::getExtensions();
        $types = [
            new PlainType($this->createMockTranslator()),
            new RoleChoiceType($this->createMockSecurity()),
            $this->createVichImageType(),
        ];
        $extensions[] = new PreloadedExtension($types, []);
        $extensions[] = $this->getPasswordHasherExtension();

        return $extensions;
    }

    protected function getFormTypeClass(): string
    {
        return UserType::class;
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
