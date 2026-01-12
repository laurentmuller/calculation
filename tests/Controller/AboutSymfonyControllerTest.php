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
use Symfony\Component\Cache\Adapter\ArrayAdapter;
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
            'symfony/composer',
            'symfony/property',
            'symfony/mime',
            'fake',
        ];
        foreach ($queries as $query) {
            foreach ($names as $name) {
                yield [$query . $name, self::ROLE_ADMIN, Response::HTTP_OK, Request::METHOD_GET, true];
            }
        }
    }

    private function createService(): PackageInfoService
    {
        $jsonPath = __DIR__ . '/../files/json/composer.lock';
        $vendorPath = __DIR__ . '/../../vendor';
        $cache = new ArrayAdapter();

        return new PackageInfoService($jsonPath, $vendorPath, $cache);
    }
}
