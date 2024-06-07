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

use App\Controller\AbstractController;
use App\Controller\AjaxController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(AbstractController::class)]
#[CoversClass(AjaxController::class)]
class AjaxControllerTest extends AbstractControllerTestCase
{
    public static function getRoutes(): \Iterator
    {
        yield ['/ajax/task', self::ROLE_USER, Response::HTTP_OK, Request::METHOD_POST, true];
        yield ['/ajax/task', self::ROLE_ADMIN, Response::HTTP_OK, Request::METHOD_POST, true];
        yield ['/ajax/task', self::ROLE_SUPER_ADMIN, Response::HTTP_OK, Request::METHOD_POST, true];

        $query = '/ajax/license?file=vendor/laurentmuller/fpdf2/LICENSE';
        yield [$query, self::ROLE_USER, Response::HTTP_FORBIDDEN, Request::METHOD_GET, true];
        yield [$query, self::ROLE_ADMIN, Response::HTTP_OK, Request::METHOD_GET, true];
        yield [$query, self::ROLE_SUPER_ADMIN, Response::HTTP_OK, Request::METHOD_GET, true];

        yield ['/ajax/random/text', self::ROLE_USER];
        yield ['/ajax/random/text', self::ROLE_ADMIN];
        yield ['/ajax/random/text', self::ROLE_SUPER_ADMIN];

        yield ['/ajax/dialog/page', self::ROLE_USER];
        yield ['/ajax/dialog/page', self::ROLE_ADMIN];
        yield ['/ajax/dialog/page', self::ROLE_SUPER_ADMIN];
    }
}
