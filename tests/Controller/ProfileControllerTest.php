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

class ProfileControllerTest extends ControllerTestCase
{
    public static function getRoutes(): \Iterator
    {
        yield ['/user/profile/password', self::ROLE_USER];
        yield ['/user/profile/password', self::ROLE_ADMIN];
        yield ['/user/profile/password', self::ROLE_SUPER_ADMIN];

        yield ['/user/profile/edit', self::ROLE_USER];
        yield ['/user/profile/edit', self::ROLE_ADMIN];
        yield ['/user/profile/edit', self::ROLE_SUPER_ADMIN];
    }

    public function testEdit(): void
    {
        $user = $this->loadUser(self::ROLE_USER);
        $data = [
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'currentPassword' => $user->getPassword(),
        ];
        $this->checkForm(
            '/user/profile/edit',
            'common.button_ok',
            $data,
            self::ROLE_USER
        );
    }

    public function testPassword(): void
    {
        $password = $this->loadUser(self::ROLE_USER)
            ->getPassword();
        $data = [
            'currentPassword' => $password,
            'plainPassword[first]' => $password,
            'plainPassword[second]' => $password,
        ];
        $this->checkForm(
            '/user/profile/password',
            'common.button_ok',
            $data,
            self::ROLE_USER
        );
    }
}
