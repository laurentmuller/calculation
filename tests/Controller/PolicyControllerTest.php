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

final class PolicyControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/policy/accept', '', Response::HTTP_FOUND];
        yield ['/policy/accept', self::ROLE_USER, Response::HTTP_FOUND];
        yield ['/policy/accept', self::ROLE_ADMIN, Response::HTTP_FOUND];
        yield ['/policy/accept', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND];
    }
}
