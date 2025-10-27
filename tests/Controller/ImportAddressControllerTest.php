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

final class ImportAddressControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/admin/import', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/admin/import', self::ROLE_ADMIN];
        yield ['/admin/import', self::ROLE_SUPER_ADMIN];
    }

    public function testImport(): void
    {
        $this->checkForm(
            'admin/import',
            'common.button_ok',
            [],
            self::ROLE_SUPER_ADMIN,
            followRedirect: false
        );
    }
}
