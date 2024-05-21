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

use App\Controller\ProductUpdateController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(ProductUpdateController::class)]
class ProductUpdateControllerTest extends AbstractControllerTestCase
{
    public static function getRoutes(): \Iterator
    {
        yield ['/admin/product', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/admin/product', self::ROLE_ADMIN];
        yield ['/admin/product', self::ROLE_SUPER_ADMIN];
    }
}
