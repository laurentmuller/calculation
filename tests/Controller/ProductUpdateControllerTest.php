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

final class ProductUpdateControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/admin/product', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/admin/product', self::ROLE_ADMIN];
        yield ['/admin/product', self::ROLE_SUPER_ADMIN];
    }
}
