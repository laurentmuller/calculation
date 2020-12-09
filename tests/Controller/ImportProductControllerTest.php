<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for {@link App\Controller\ImportProductController} class.
 *
 * @author Laurent Muller
 */
class ImportProductControllerTest extends AbstractControllerTest
{
    public function getRoutes(): array
    {
        return [
            ['/product/import', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/product/import', self::ROLE_ADMIN],
            ['/product/import', self::ROLE_SUPER_ADMIN],
        ];
    }
}
