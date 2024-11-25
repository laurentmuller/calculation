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

namespace App\Tests\Web;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for users and routes.
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
class RoutesTest extends AuthenticateWebTestCase
{
    public static function getRoutes(): \Iterator
    {
        // not exist
        yield ['/not_exist', self::ROLE_USER, Response::HTTP_NOT_FOUND];
    }

    #[DataProvider('getRoutes')]
    public function testRoutes(string $url, string $username, int $expected = Response::HTTP_OK): void
    {
        $this->loginUsername($username);
        $this->client->request(Request::METHOD_GET, $url);
        $this->checkResponse($url, $username, $expected);
    }
}
