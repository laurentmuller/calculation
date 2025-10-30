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

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Service to get information about routes.
 *
 * @phpstan-type RouteType = array{
 *      name: string,
 *      path: string,
 *      debug: bool,
 *      methods: string}
 */
class RouteInfoService
{
    public function __construct(
        private readonly RouterInterface $router,
        #[Target('calculation.symfony')]
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * Gets debug routes.
     *
     * @return array<string, RouteType>
     */
    public function getDebugRoutes(): array
    {
        return \array_filter($this->getRoutes(), static fn (array $route): bool => $route['debug']);
    }

    /**
     * @phpstan-return array<string, RouteType>
     */
    public function getRoutes(): array
    {
        return $this->cache->get('routes', $this->loadRoutes(...));
    }

    /**
     * Gets runtime routes.
     *
     * @return array<string, RouteType>
     */
    public function getRuntimeRoutes(): array
    {
        return \array_filter($this->getRoutes(), static fn (array $route): bool => !$route['debug']);
    }

    private function isDebugRoute(string $name): bool
    {
        return \str_starts_with($name, '_');
    }

    /**
     * @phpstan-return array<string, RouteType>
     */
    private function loadRoutes(): array
    {
        $result = [];
        $routes = $this->router->getRouteCollection()->all();
        foreach ($routes as $name => $route) {
            $debug = $this->isDebugRoute($name);
            $result[$name] = $this->parseRoute($name, $debug, $route);
        }

        return $result;
    }

    /**
     * @phpstan-return RouteType
     */
    private function parseRoute(string $name, bool $debug, Route $route): array
    {
        $methods = $route->getMethods();

        return [
            'name' => $name,
            'debug' => $debug,
            'path' => $route->getPath(),
            'methods' => [] === $methods ? 'ANY' : \implode(', ', $methods),
        ];
    }
}
