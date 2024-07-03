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
use App\Form\User\UserChangePasswordType;
use App\Tests\Form\EntityTypeTestCase;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Form\PreloadedExtension;

/**
 * @extends EntityTypeTestCase<User, UserChangePasswordType>
 */
#[CoversClass(UserChangePasswordType::class)]
class UserChangePasswordTypeTest extends EntityTypeTestCase
{
    use PasswordHasherExtensionTrait;
    use TranslatorMockTrait;

    protected function getData(): array
    {
        return [
            'username' => '',
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
        ];
        $extensions[] = new PreloadedExtension($types, []);
        $extensions[] = $this->getPasswordHasherExtension();

        return $extensions;
    }

    protected function getFormTypeClass(): string
    {
        return UserChangePasswordType::class;
    }
}
