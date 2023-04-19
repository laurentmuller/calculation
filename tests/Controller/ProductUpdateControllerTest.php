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
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(ProductUpdateController::class)]
class ProductUpdateControllerTest extends AbstractControllerTestCase
{
    public static function getRoutes(): array
    {
        return [
            ['/admin/product', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/admin/product', self::ROLE_ADMIN],
            ['/admin/product', self::ROLE_SUPER_ADMIN],
        ];
    }
}
