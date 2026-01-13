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

use App\Service\PackageInfoService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AboutSymfonyControllerTest extends ControllerTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->setService(PackageInfoService::class, $this->createService());
    }

    #[\Override]
    public static function getRoutes(): \Generator
    {
        $routes = [
            '/about/symfony/content',
            '/about/symfony/excel',
            '/about/symfony/pdf',
        ];
        // xmlHttpRequest
        foreach ($routes as $route) {
            yield [$route, self::ROLE_USER, Response::HTTP_FORBIDDEN];
            yield [$route, self::ROLE_ADMIN];
            yield [$route, self::ROLE_SUPER_ADMIN];
        }

        $queries = [
            '/about/symfony/license?name=',
            '/about/symfony/dependency?name=',
        ];
        $names = [
            'symfony/finder',
            'symfony/asset',
            'symfony/fake',
        ];
        foreach ($queries as $query) {
            foreach ($names as $name) {
                yield [$query . $name, self::ROLE_ADMIN, Response::HTTP_OK, Request::METHOD_GET, true];
            }
        }
    }

    private function createService(): PackageInfoService
    {
        $finderPackage = [
            'name' => 'symfony/finder',
            'version' => '1.8.2',
            'description' => 'Finder description.',
            'homepage' => 'https://symfony.com/',
            'license' => __DIR__ . '/../../vendor/symfony/finder/LICENSE',
            'time' => '01.01.2025',
            'debug' => false,
            'production' => ['composer-plugin-api' => '^2.0'],
            'development' => [],
        ];
        $assetPackage = [
            'name' => 'symfony/asset',
            'version' => '1.8.2',
            'description' => 'Asset description.',
            'homepage' => 'https://symfony.com/',
            'license' => null,
            'time' => '02.01.2025',
            'debug' => true,
            'production' => [],
            'development' => [],
        ];
        $packages = [
            'symfony/finder' => $finderPackage,
            'symfony/asset' => $assetPackage,
        ];

        $service = $this->createMock(PackageInfoService::class);
        $service->method('getPackages')
            ->willReturn($packages);
        $service->method('getRuntimePackages')
            ->willReturn($packages);
        $service->method('getDebugPackages')
            ->willReturn($packages);

        $service->method('hasPackage')
            ->willReturnMap([
                ['symfony/finder', true],
                ['symfony/asset', true],
                ['symfony/fake', false],
            ]);

        $service->method('getPackage')
            ->willReturnMap([
                ['symfony/finder', $finderPackage],
                ['symfony/asset', $assetPackage],
                ['symfony/fake', []],
            ]);

        return $service;
    }
}
