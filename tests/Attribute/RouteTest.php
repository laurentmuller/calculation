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

namespace App\Tests\Attribute;

use App\Attribute\GetDeleteRoute;
use App\Attribute\GetPostRoute;
use App\Attribute\GetRoute;
use App\Attribute\PostRoute;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

/**
 * Unit test for route attributes.
 */
class RouteTest extends TestCase
{
    private const NAME_VALUE = 'value_edit';
    private const PATH_VALUE = '/edit/{id}';
    private const REQUIREMENTS = ['id' => Requirement::DIGITS];

    public function testGet(): void
    {
        $route = new GetRoute(self::PATH_VALUE, self::NAME_VALUE, self::REQUIREMENTS);
        self::assertSameRoute($route, Request::METHOD_GET);
    }

    public function testGetDelete(): void
    {
        $route = new GetDeleteRoute(self::PATH_VALUE, self::NAME_VALUE, self::REQUIREMENTS);
        self::assertSameRoute($route, Request::METHOD_GET, Request::METHOD_DELETE);
    }

    public function testGetPost(): void
    {
        $route = new GetPostRoute(self::PATH_VALUE, self::NAME_VALUE, self::REQUIREMENTS);
        self::assertSameRoute($route, Request::METHOD_GET, Request::METHOD_POST);
    }

    public function testPost(): void
    {
        $route = new PostRoute(self::PATH_VALUE, self::NAME_VALUE, self::REQUIREMENTS);
        self::assertSameRoute($route, Request::METHOD_POST);
    }

    protected static function assertSameRoute(Route $route, string ...$methods): void
    {
        self::assertSame(self::PATH_VALUE, $route->getPath());
        self::assertSame(self::NAME_VALUE, $route->getName());
        self::assertSame(self::REQUIREMENTS, $route->getRequirements());
        self::assertSame($methods, $route->getMethods());

        // check override
        $route->setMethods('fake');
        self::assertSame($methods, $route->getMethods());
    }
}
