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

/**
 * Unit test for {@link RegistrationController} class.
 */
class RegistrationControllerTest extends AbstractControllerTest
{
    public function getRoutes(): array
    {
        return [
            ['/register', self::ROLE_USER],
            ['/register', self::ROLE_ADMIN],
            ['/register', self::ROLE_SUPER_ADMIN],

            ['/register/verify', self::ROLE_USER, Response::HTTP_FOUND],
            ['/register/verify', self::ROLE_ADMIN, Response::HTTP_FOUND],
            ['/register/verify', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND],
        ];
    }
}
