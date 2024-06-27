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

use App\Controller\ExchangeRateController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(ExchangeRateController::class)]
class ExchangeRateControllerTest extends AbstractControllerTestCase
{
    public static function getRoutes(): \Iterator
    {
        yield ['/exchange', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/exchange', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
        yield ['/exchange', self::ROLE_SUPER_ADMIN];

        yield ['/exchange/codes', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/exchange/codes', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
        yield ['/exchange/codes', self::ROLE_SUPER_ADMIN];

        yield ['/exchange/latest/CHF', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/exchange/latest/CHF', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
        yield ['/exchange/latest/CHF', self::ROLE_SUPER_ADMIN];

        yield ['/exchange/rate?baseCode=CHF&targetCode=EUR', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/exchange/rate?baseCode=CHF&targetCode=EUR', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
        yield ['/exchange/rate?baseCode=CHF&targetCode=EUR', self::ROLE_SUPER_ADMIN];
    }
}
