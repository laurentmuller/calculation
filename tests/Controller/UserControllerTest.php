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

use App\Controller\UserController;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(UserController::class)]
class UserControllerTest extends AbstractControllerTestCase
{
    public static function getRoutes(): \Iterator
    {
        yield ['/user', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user', self::ROLE_ADMIN];
        yield ['/user', self::ROLE_SUPER_ADMIN];
        yield ['/user', self::ROLE_DISABLED, Response::HTTP_FORBIDDEN];
        yield ['/user/add', self::ROLE_ADMIN];
        yield ['/user/add', self::ROLE_SUPER_ADMIN];
        yield ['/user/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/edit/1', self::ROLE_ADMIN];
        yield ['/user/edit/1', self::ROLE_SUPER_ADMIN];
        yield ['/user/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/delete/1', self::ROLE_ADMIN];
        // can delete when connected
        yield ['/user/delete/1', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND];
        yield ['/user/delete/2', self::ROLE_SUPER_ADMIN];
        yield ['/user/show/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/show/1', self::ROLE_ADMIN];
        yield ['/user/show/1', self::ROLE_SUPER_ADMIN];
        yield ['/user/password/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/password/1', self::ROLE_ADMIN];
        yield ['/user/password/1', self::ROLE_SUPER_ADMIN];
        yield ['/user/rights/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/rights/1', self::ROLE_ADMIN];
        yield ['/user/rights/1', self::ROLE_SUPER_ADMIN];
        yield ['/user/rights/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/rights/pdf', self::ROLE_ADMIN];
        yield ['/user/rights/pdf', self::ROLE_SUPER_ADMIN];
        yield ['/user/rights/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/rights/excel', self::ROLE_ADMIN];
        yield ['/user/rights/excel', self::ROLE_SUPER_ADMIN];
        yield ['/user/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/pdf', self::ROLE_ADMIN];
        yield ['/user/pdf', self::ROLE_SUPER_ADMIN];
        yield ['/user/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/excel', self::ROLE_ADMIN];
        yield ['/user/excel', self::ROLE_SUPER_ADMIN];
        yield ['/user/parameters', self::ROLE_USER];
        yield ['/user/parameters', self::ROLE_ADMIN];
        yield ['/user/parameters', self::ROLE_SUPER_ADMIN];
    }
}
