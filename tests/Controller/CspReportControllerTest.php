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

use App\Controller\CspReportController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(CspReportController::class)]
class CspReportControllerTest extends ControllerTestCase
{
    public static function getRoutes(): \Iterator
    {
        yield ['/csp', self::ROLE_USER, Response::HTTP_NO_CONTENT];
    }
}
