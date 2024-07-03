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
use App\Form\User\ProfileChangePasswordType;
use App\Tests\Form\EntityTypeTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;

/**
 * @extends EntityTypeTestCase<User, ProfileChangePasswordType>
 */
#[CoversClass(ProfileChangePasswordType::class)]
class ProfileChangePasswordTypeTest extends EntityTypeTestCase
{
    use PasswordHasherExtensionTrait;

    protected function getData(): array
    {
        return [
            'username' => 'username',
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
        $extensions[] = $this->getPasswordHasherExtension();

        return $extensions;
    }

    protected function getFormTypeClass(): string
    {
        return ProfileChangePasswordType::class;
    }
}
