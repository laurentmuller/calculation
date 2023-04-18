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

use App\Controller\AskimetController;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(AskimetController::class)]
class AskimetControllerTest extends AbstractTestController
{
    private const ROUTES = [
        'spam',
        'verify',
    ];

    public static function getRoutes(): \Generator
    {
        foreach (self::ROUTES as $route) {
            yield ["/askimet/$route", self::ROLE_USER, Response::HTTP_FORBIDDEN];
            yield ["/askimet/$route", self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
            yield ["/askimet/$route", self::ROLE_SUPER_ADMIN];
        }
    }
}
