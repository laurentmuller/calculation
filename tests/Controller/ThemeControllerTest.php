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

use App\Controller\ThemeController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(ThemeController::class)]
class ThemeControllerTest extends AbstractControllerTestCase
{
    public static function getRoutes(): array
    {
        return [
            ['/theme/dialog', self::ROLE_USER, Response::HTTP_OK, Request::METHOD_GET, true],
            ['/theme/dialog', self::ROLE_ADMIN, Response::HTTP_OK, Request::METHOD_GET, true],
            ['/theme/dialog', self::ROLE_SUPER_ADMIN, Response::HTTP_OK, Request::METHOD_GET, true],
        ];
    }
}
