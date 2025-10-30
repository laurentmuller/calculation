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

namespace App\Tests\Service;

use App\Service\RouteInfoService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

final class RouteInfoServiceTest extends TestCase
{
    public function testGetDebugRoutes(): void
    {
        $service = $this->createService();
        $actual = $service->getDebugRoutes();
        self::assertCount(1, $actual);
    }

    public function testGetRoutes(): void
    {
        $service = $this->createService();
        $actual = $service->getRoutes();
        self::assertCount(2, $actual);
    }

    public function testGetRuntimeRoutes(): void
    {
        $service = $this->createService();
        $actual = $service->getRuntimeRoutes();
        self::assertCount(1, $actual);
    }

    private function createService(): RouteInfoService
    {
        $routes = [
            'index' => new Route('home'),
            '_profiler' => new Route('_profiler'),
        ];
        $collection = $this->createMock(RouteCollection::class);
        $collection->method('all')
            ->willReturn($routes);
        $router = $this->createMock(RouterInterface::class);
        $router->method('getRouteCollection')
            ->willReturn($collection);

        return new RouteInfoService($router, new ArrayAdapter());
    }
}
