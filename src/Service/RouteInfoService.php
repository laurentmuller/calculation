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

use App\Constants\CacheAttributes;
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
 *      methods: string[]}
 */
readonly class RouteInfoService
{
    public function __construct(
        private RouterInterface $router,
        #[Target(CacheAttributes::CACHE_SYMFONY)]
        private CacheInterface $cache,
    ) {
    }

    /**
     * Gets debug routes.
     *
     * @return array<string, RouteType>
     */
    public function getDebugRoutes(): array
    {
        return $this->filterRoutes(true);
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
        return $this->filterRoutes(false);
    }

    /**
     * @return array<string, RouteType>
     */
    private function filterRoutes(bool $debug): array
    {
        return \array_filter($this->getRoutes(), static fn (array $route): bool => $debug === $route['debug']);
    }

    /**
     * @phpstan-return array<string, RouteType>
     */
    private function loadRoutes(): array
    {
        $result = [];
        $routes = $this->router->getRouteCollection()->all();
        foreach ($routes as $name => $route) {
            $path = $route->getPath();
            $result[$name] = [
                'name' => $name,
                'path' => $path,
                'debug' => \str_starts_with($path, '/_'),
                'methods' => $this->parseMethod($route),
            ];
        }
        \ksort($result);

        return $result;
    }

    private function parseMethod(Route $route): array
    {
        $methods = $route->getMethods();

        return [] === $methods ? ['ANY'] : $methods;
    }
}
