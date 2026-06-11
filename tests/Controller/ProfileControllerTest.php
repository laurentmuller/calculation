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

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\Depends;

final class ProfileControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/user/profile/password', self::ROLE_USER];
        yield ['/user/profile/password', self::ROLE_ADMIN];
        yield ['/user/profile/password', self::ROLE_SUPER_ADMIN];

        yield ['/user/profile/edit', self::ROLE_USER];
        yield ['/user/profile/edit', self::ROLE_ADMIN];
        yield ['/user/profile/edit', self::ROLE_SUPER_ADMIN];
    }

    /**
     * Must the last one because it changes the username.
     */
    #[Depends('testRoutes')]
    #[Depends('testPassword')]
    #[Depends('testEditWithoutChange')]
    public function testEditWithChange(): void
    {
        $user = $this->loadUser(self::ROLE_USER);
        $oldUserName = $user->getUsername();
        self::assertNotNull($user);

        $data = [
            'username' => 'new_username',
            'email' => $user->getEmail(),
            'currentPassword' => $user->getPassword(),
        ];

        $this->checkForm(
            uri: '/user/profile/edit',
            data: $data,
            userName: self::ROLE_USER
        );
    }

    public function testEditWithoutChange(): void
    {
        $user = $this->loadUser(self::ROLE_USER);
        self::assertNotNull($user);
        $data = [
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'currentPassword' => $user->getPassword(),
        ];
        $this->checkForm(
            uri: '/user/profile/edit',
            data: $data,
            userName: self::ROLE_USER
        );
    }

    public function testPassword(): void
    {
        $user = $this->loadUser(self::ROLE_USER);
        self::assertNotNull($user);
        $password = $user->getPassword();
        $data = [
            'currentPassword' => $password,
            'plainPassword[first]' => $password,
            'plainPassword[second]' => $password,
        ];
        $this->checkForm(
            uri: '/user/profile/password',
            data: $data,
            userName: self::ROLE_USER
        );
    }
}
