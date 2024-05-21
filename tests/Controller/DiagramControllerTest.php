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

use App\Controller\DiagramController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(DiagramController::class)]
class DiagramControllerTest extends AbstractControllerTestCase
{
    public static function getRoutes(): \Iterator
    {
        yield ['/diagram', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/diagram', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
        yield ['/diagram', self::ROLE_SUPER_ADMIN];

        yield ['/diagram/load', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/diagram/load', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
        yield ['/diagram/load', self::ROLE_SUPER_ADMIN];
    }
}
