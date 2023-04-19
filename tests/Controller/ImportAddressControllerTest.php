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

use App\Controller\ImportAddressController;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(ImportAddressController::class)]
class ImportAddressControllerTest extends AbstractControllerTestCase
{
    public static function getRoutes(): array
    {
        return [
            ['/admin/import', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/admin/import', self::ROLE_ADMIN],
            ['/admin/import', self::ROLE_SUPER_ADMIN],
        ];
    }
}
