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

use App\Controller\ResetPasswordController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for {@link ResetPasswordController} class.
 */
class ResetPasswordControllerTest extends AbstractControllerTest
{
    public function getRoutes()
    {
        return [
            ['/reset-password', self::ROLE_USER],
            ['/reset-password', self::ROLE_ADMIN],
            ['/reset-password', self::ROLE_SUPER_ADMIN],

            ['/reset-password/check-email', self::ROLE_USER],
            ['/reset-password/check-email', self::ROLE_ADMIN],
            ['/reset-password/check-email', self::ROLE_SUPER_ADMIN],

            ['/reset-password/reset/', self::ROLE_USER, Response::HTTP_MOVED_PERMANENTLY],
            ['/reset-password/reset/', self::ROLE_ADMIN, Response::HTTP_MOVED_PERMANENTLY],
            ['/reset-password/reset/', self::ROLE_SUPER_ADMIN, Response::HTTP_MOVED_PERMANENTLY],

            ['/reset-password/reset/fake', self::ROLE_USER, Response::HTTP_FOUND],
            ['/reset-password/reset/fake', self::ROLE_ADMIN, Response::HTTP_FOUND],
            ['/reset-password/reset/fake', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND],
        ];
    }
}
