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

namespace App\Tests\Report;

use App\Enums\Environment;
use App\Interfaces\DocumentHelperInterface;
use App\Report\SymfonyReport;
use App\Service\BundleInfoService;
use App\Service\FontAwesomeCellService;
use App\Service\KernelInfoService;
use App\Service\PackageInfoService;
use App\Service\RouteInfoService;
use App\Service\SymfonyInfoService;
use PHPUnit\Framework\TestCase;

final class SymfonyReportTest extends TestCase
{
    public function testRenderEmpty(): void
    {
        $report = $this->createReport();
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testWithBundleService(): void
    {
        $name = 'SecurityBundle';
        $bundle = [
            'name' => $name,
            'namespace' => 'Symfony\Bundle\SecurityBundle',
            'path' => 'vendor/symfony/security-bundle',
            'package' => 'src/Bundle/FrameworkBundle',
            'files' => '13',
            'size' => '1.3 MB',
        ];
        $bundles = [$name => $bundle];
        $bundleService = self::createStub(BundleInfoService::class);
        $bundleService->method('getBundles')
            ->willReturn($bundles);

        $report = $this->createReport(bundleService: $bundleService);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testWithPackageService(): void
    {
        $name = 'symfony/runtime';
        $package = [
            'name' => $name,
            'version' => '6.0.0',
            'description' => 'Runtime description.',
            'homepage' => 'https://symfony.com/doc/current/runtimes.html',
            'licenseFile' => null,
            'licenseType' => [],
            'source' => null,
            'time' => '05.06.2026',
            'debug' => true,
            'production' => [],
            'development' => [],
        ];
        $packages = [$name => $package];
        $packageService = self::createStub(PackageInfoService::class);
        $packageService->method('getPackages')
            ->willReturn($packages);
        $packageService->method('getRuntimePackages')
            ->willReturn($packages);
        $packageService->method('getDebugPackages')
            ->willReturn($packages);

        $report = $this->createReport(packageService: $packageService);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testWithRouteService(): void
    {
        $name = 'app_home';
        $route = [
            'name' => $name,
            'path' => '/home',
            'debug' => false,
            'methods' => ['ANY', 'DELETE', 'GET', 'POST'],
        ];
        $routes = [$name => $route];
        $routeService = self::createStub(RouteInfoService::class);
        $routeService->method('getRoutes')
            ->willReturn($routes);
        $routeService->method('getDebugRoutes')
            ->willReturn($routes);
        $routeService->method('getRuntimeRoutes')
            ->willReturn($routes);

        $report = $this->createReport(routeService: $routeService);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    private function createKernelInfoService(): KernelInfoService
    {
        $directory = [
            'name' => 'fake',
            'path' => '/local/cache',
            'relative' => '/cache',
            'size' => '100 MB',
        ];
        $kernelService = self::createStub(KernelInfoService::class);
        $kernelService->method('getApplicationEnvironment')
            ->willReturn(Environment::PRODUCTION);
        $kernelService->method('getKernelEnvironment')
            ->willReturn(Environment::PRODUCTION);
        $kernelService->method('getCacheInfo')
            ->willReturn($directory);
        $kernelService->method('getBuildInfo')
            ->willReturn($directory);
        $kernelService->method('getLogInfo')
            ->willReturn($directory);

        return $kernelService;
    }

    private function createReport(
        ?BundleInfoService $bundleService = null,
        ?RouteInfoService $routeService = null,
        ?PackageInfoService $packageService = null
    ): SymfonyReport {
        $helper = self::createStub(DocumentHelperInterface::class);
        $bundleService ??= self::createStub(BundleInfoService::class);
        $kernelService = $this->createKernelInfoService();
        $routeService ??= self::createStub(RouteInfoService::class);
        $packageService ??= self::createStub(PackageInfoService::class);
        $symfonyService = self::createStub(SymfonyInfoService::class);
        $cellService = self::createStub(FontAwesomeCellService::class);

        return new SymfonyReport(
            helper: $helper,
            bundleService: $bundleService,
            kernelService: $kernelService,
            routeService: $routeService,
            packageService: $packageService,
            symfonyService: $symfonyService,
            cellService: $cellService,
        );
    }
}
