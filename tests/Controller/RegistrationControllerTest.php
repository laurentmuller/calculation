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

use Symfony\Component\HttpFoundation\Response;

class RegistrationControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Iterator
    {
        yield ['/register', self::ROLE_USER];
        yield ['/register', self::ROLE_ADMIN];
        yield ['/register', self::ROLE_SUPER_ADMIN];
        yield ['/register/verify', self::ROLE_USER, Response::HTTP_FOUND];
        yield ['/register/verify', self::ROLE_ADMIN, Response::HTTP_FOUND];
        yield ['/register/verify', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND];
    }

    public function testRegister(): void
    {
        $data = [
            'username' => 'user_name',
            'email' => 'email@email.com',
            'plainPassword[first]' => '12345@#POA457az',
            'plainPassword[second]' => '12345@#POA457az',
            'agreeTerms' => 1,
        ];
        $this->checkForm(
            '/register',
            'registration.register.submit',
            $data,
            self::ROLE_SUPER_ADMIN
        );
    }
}
