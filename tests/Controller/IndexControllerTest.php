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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/', self::ROLE_DISABLED];
        yield ['/', self::ROLE_USER];
        yield ['/', self::ROLE_ADMIN];
        yield ['/', self::ROLE_SUPER_ADMIN];
        yield ['/?custom=1&restrict=1', self::ROLE_USER];
        yield ['/?custom=1&restrict=1&count=8', self::ROLE_USER];
        yield ['/hide/catalog', self::ROLE_USER, Response::HTTP_OK,  Request::METHOD_POST, true];
        yield ['/hide/month', self::ROLE_USER, Response::HTTP_OK,  Request::METHOD_POST, true];
        yield ['/hide/state', self::ROLE_USER, Response::HTTP_OK,  Request::METHOD_POST, true];
    }

    public function testInvalidRequest(): void
    {
        $this->loginUsername(self::ROLE_USER);
        $this->client->request(Request::METHOD_POST, '/hide/catalog');
        $actual = $this->client->getResponse()->getStatusCode();
        self::assertSame(Response::HTTP_BAD_REQUEST, $actual);
    }
}
