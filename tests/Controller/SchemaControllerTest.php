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

/**
 * Unit test for {@link App\Controller\SchemaController} class.
 *
 * @author Laurent Muller
 */
class SchemaControllerTest extends AbstractControllerTest
{
    /**
     * {@inheritDoc}
     */
    public function getRoutes(): array
    {
        return [
            ['/schema', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/schema', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            ['/schema', self::ROLE_SUPER_ADMIN],

            ['/schema/sy_Calculation', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/schema/sy_Calculation', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            ['/schema/sy_Calculation', self::ROLE_SUPER_ADMIN],
        ];
    }
}
