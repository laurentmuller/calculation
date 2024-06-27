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

use App\Controller\RegistrationController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(RegistrationController::class)]
class RegistrationControllerTest extends ControllerTestCase
{
    public static function getRoutes(): \Iterator
    {
        yield ['/register', self::ROLE_USER];
        yield ['/register', self::ROLE_ADMIN];
        yield ['/register', self::ROLE_SUPER_ADMIN];
        yield ['/register/verify', self::ROLE_USER, Response::HTTP_FOUND];
        yield ['/register/verify', self::ROLE_ADMIN, Response::HTTP_FOUND];
        yield ['/register/verify', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND];
    }
}
