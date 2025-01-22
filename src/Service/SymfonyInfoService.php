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

use App\Enums\Environment;
use App\Utils\DateUtils;
use App\Utils\FileUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Intl\Locales;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Utility class to get Symfony information.
 *
 * @see https://github.com/symfony/symfony/blob/7.1/src/Symfony/Bundle/FrameworkBundle/Command/AboutCommand.php
 * @see https://github.com/EasyCorp/easy-doc-bundle/blob/master/src/Command/DocCommand.php
 *
 * @psalm-type RouteType = array{
 *     name: string,
 *     path: string,
 *     methods: string}
 * @psalm-type RoutesType = array{
 *     runtime: array<string, RouteType>,
 *     debug: array<string, RouteType>}
 * @psalm-type PackageSourceType = array{
 *     name: string,
 *     version: string,
 *     description?: string,
 *     homepage?: string,
 *     install-path: string,
 *     support?: array{source?: string}}
 * @psalm-type PackageType = array{
 *     name: string,
 *     version: string,
 *     description: string,
 *     homepage: string|null,
 *     license: string|null}
 * @psalm-type PackagesType = array{
 *     runtime: array<string, PackageType>,
 *     debug: array<string, PackageType>}
 * @psalm-type BundleType = array{
 *     name: string,
 *     namespace: string,
 *     path: string,
 *     package: string,
 *     size: string}
 */
final readonly class SymfonyInfoService
{
    // the array key for debug packages and routes
    private const KEY_DEBUG = 'debug';
    // the array key for runtime packages and routes
    private const KEY_RUNTIME = 'runtime';
    // the pattern to search the license file
    private const LICENSE_PATTERN = '/license(\.txt|\.md)?$/i';
    // the JSON file containing composer information
    private const PACKAGE_FILE_NAME = '/vendor/composer/installed.json';
    // the release information URL
    private const RELEASE_URL = 'https://symfony.com/releases/%s.%s.json';
    // the unknown label
    private const UNKNOWN = 'Unknown';

    private Environment $environment;
    private Environment $mode;
    private string $projectDir;

    public function __construct(
        private KernelInterface $kernel,
        private RouterInterface $router,
        #[Target('calculation.symfony')]
        private CacheInterface $cache,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
        #[Autowire('%app_mode%')]
        string $app_mode
    ) {
        $this->projectDir = FileUtils::normalizeDirectory($projectDir);
        $this->environment = Environment::fromKernel($this->kernel);
        $this->mode = Environment::from($app_mode);
    }

    /**
     * Get architecture.
     */
    public function getArchitecture(): string
    {
        return \sprintf('%d bits', \PHP_INT_SIZE * 8);
    }

    /**
     * Gets the build directory path and the formatted size.
     */
    public function getBuildInfo(): string
    {
        return $this->getDirectoryInfo($this->kernel->getBuildDir());
    }

    /**
     * Gets bundle's information.
     *
     * @psalm-return array<string, BundleType>
     */
    public function getBundles(): array
    {
        return $this->cache->get('bundles', function (): array {
            $bundles = [];
            $projectDir = $this->projectDir;
            $vendorDir = FileUtils::buildPath($projectDir, 'vendor');
            foreach ($this->kernel->getBundles() as $key => $bundleObject) {
                $path = $bundleObject->getPath();
                $bundles[$key] = [
                    'name' => $key,
                    'namespace' => $bundleObject->getNamespace(),
                    'path' => $this->makePathRelative($path, $projectDir),
                    'package' => $this->makePathRelative($path, $vendorDir),
                    'size' => FileUtils::formatSize($path),
                ];
            }
            \ksort($bundles);

            return $bundles;
        });
    }

    /**
     * Gets the cache directory path and the formatted size.
     */
    public function getCacheInfo(): string
    {
        return $this->getDirectoryInfo($this->kernel->getCacheDir());
    }

    /**
     * Gets the charset of the application.
     */
    public function getCharset(): string
    {
        return $this->kernel->getCharset();
    }

    /**
     * Gets the debug packages.
     *
     * @return array<string, PackageType>
     */
    public function getDebugPackages(): array
    {
        return $this->getPackages()[self::KEY_DEBUG];
    }

    /**
     * Gets debug routes.
     *
     * @return array<string, RouteType>
     */
    public function getDebugRoutes(): array
    {
        return $this->getRoutes()[self::KEY_DEBUG];
    }

    /**
     * Gets the end of life.
     */
    public function getEndOfLife(): string
    {
        $date = $this->formatMonthYear(Kernel::END_OF_LIFE);
        $days = $this->getDaysBeforeExpiration(Kernel::END_OF_LIFE);

        return \sprintf('%s (%s)', $date, $days);
    }

    /**
     * Gets the end of maintenance.
     */
    public function getEndOfMaintenance(): string
    {
        $date = $this->formatMonthYear(Kernel::END_OF_MAINTENANCE);
        $days = $this->getDaysBeforeExpiration(Kernel::END_OF_MAINTENANCE);

        return \sprintf('%s (%s)', $date, $days);
    }

    /**
     * Gets the kernel environment.
     */
    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /**
     * Get the local name.
     */
    public function getLocaleName(): string
    {
        $locale = \Locale::getDefault();
        $name = Locales::getName($locale, 'en');

        return "$name - $locale";
    }

    /**
     * Gets the log directory path and the formatted size.
     */
    public function getLogInfo(): string
    {
        return $this->getDirectoryInfo($this->kernel->getLogDir());
    }

    /**
     * Gets the maintenance status.
     */
    public function getMaintenanceStatus(): string
    {
        $now = new \DateTimeImmutable();
        $eol = $this->getEndOfMonth(Kernel::END_OF_LIFE);
        $eom = $this->getEndOfMonth(Kernel::END_OF_MAINTENANCE);
        if ($now > $eol) {
            return 'Unmaintained';
        }
        if ($now > $eom) {
            return 'Security Fixes Only';
        }

        return 'Maintained';
    }

    /**
     * Gets the application mode.
     */
    public function getMode(): Environment
    {
        return $this->mode;
    }

    /**
     * Gets the project directory path.
     */
    public function getProjectDir(): string
    {
        return $this->projectDir;
    }

    /**
     * Get the release date.
     *
     * @psalm-api
     */
    public function getReleaseDate(): string
    {
        return $this->cache->get('release', function (): string {
            $url = \sprintf(self::RELEASE_URL, Kernel::MAJOR_VERSION, Kernel::MINOR_VERSION);

            try {
                /** @psalm-var array{release_date: string, ...} $content */
                $content = FileUtils::decodeJson($url);
                $date = $content['release_date'];

                return $this->formatMonthYear($date);
            } catch (\InvalidArgumentException) {
                return self::UNKNOWN;
            }
        });
    }

    /**
     * Gets the runtime packages.
     *
     * @return array<string, PackageType>
     */
    public function getRuntimePackages(): array
    {
        return $this->getPackages()[self::KEY_RUNTIME];
    }

    /**
     * Gets runtime routes.
     *
     * @return array<string, RouteType>
     */
    public function getRuntimeRoutes(): array
    {
        return $this->getRoutes()[self::KEY_RUNTIME];
    }

    /**
     * Gets the time zone.
     */
    public function getTimeZone(): string
    {
        return \date_default_timezone_get();
    }

    /**
     * Gets the kernel version.
     */
    public function getVersion(): string
    {
        return Kernel::VERSION;
    }

    /**
     * Returns if the 'apcu' extension is loaded and enabled.
     */
    public function isApcuLoaded(): bool
    {
        return $this->isExtensionLoaded('apcu', 'apc.enabled');
    }

    /**
     * Gets if debug mode is enabled.
     */
    public function isDebug(): bool
    {
        return $this->kernel->isDebug();
    }

    /**
     * Returns if the long-term support is enabled.
     */
    public function isLongTermSupport(): bool
    {
        // @phpstan-ignore identical.alwaysFalse
        return (4 <=> Kernel::MINOR_VERSION) === 0;
    }

    /**
     * Returns if the 'xdebug' extension is loaded.
     */
    public function isXdebugLoaded(): bool
    {
        return $this->isExtensionLoaded('xdebug');
    }

    /**
     * Returns if the 'Zend OP cache' extension is loaded and enabled.
     */
    public function isZendCacheLoaded(): bool
    {
        return $this->isExtensionLoaded('Zend OPcache', 'opcache.enable');
    }

    private function cleanDescription(string $description): string
    {
        if ('' !== $description && !\str_ends_with($description, '.')) {
            return $description . '.';
        }

        return $description;
    }

    private function createDate(string $date): \DateTimeImmutable
    {
        /** @psalm-var \DateTimeImmutable */
        return \DateTimeImmutable::createFromFormat('d/m/Y', '01/' . $date);
    }

    /**
     * Format a date.
     *
     * @param string $date the date (month/year) to format
     */
    private function formatMonthYear(string $date): string
    {
        return $this->createDate($date)
            ->format('F Y');
    }

    /**
     * Formats the given path within the given optional base path.
     *
     * @param string  $path    the path
     * @param ?string $baseDir the base (root) path
     *
     * @return string the relative path
     */
    private function formatPath(string $path, ?string $baseDir = null): string
    {
        if (null !== $baseDir) {
            return FileUtils::makePathRelative($path, $baseDir, true);
        }

        return FileUtils::normalizeDirectory($path);
    }

    private function getDaysBeforeExpiration(string $date): string
    {
        $today = new \DateTimeImmutable();
        $date = $this->getEndOfMonth($date);

        return $today->diff($date)->format('%R%a days');
    }

    private function getDirectoryInfo(string $path): string
    {
        $relativePath = $this->formatPath($path, $this->projectDir);
        $size = FileUtils::formatSize($path);

        return \sprintf('%s (%s)', $relativePath, $size);
    }

    /**
     * Gets empty packages.
     *
     * @return PackagesType
     */
    private function getEmptyPackages(): array
    {
        return [
            self::KEY_RUNTIME => [],
            self::KEY_DEBUG => [],
        ];
    }

    /**
     * Gets the end of the month date.
     *
     * @param string $date the date as month/year format
     */
    private function getEndOfMonth(string $date): \DateTimeImmutable
    {
        return DateUtils::modify($this->createDate($date), 'last day of this month 23:59:59');
    }

    /**
     * @psalm-param PackageSourceType $package
     */
    private function getLicense(array $package): ?string
    {
        $dir = FileUtils::buildPath(
            $this->projectDir,
            'vendor/composer',
            $package['install-path']
        );
        $finder = Finder::create()
            ->in($dir)->depth(0)->files()
            ->name(self::LICENSE_PATTERN);
        foreach ($finder as $file) {
            return FileUtils::makePathRelative($file->getRealPath(), $this->projectDir);
        }

        return null;
    }

    /**
     * Gets packages information.
     *
     * @return PackagesType
     */
    private function getPackages(): array
    {
        return $this->cache->get('packages', function (): array {
            $path = FileUtils::buildPath($this->projectDir, self::PACKAGE_FILE_NAME);
            if (!FileUtils::exists($path)) {
                return $this->getEmptyPackages();
            }

            /**
             * @psalm-var array{
             *     packages: array<string, PackageSourceType>|null,
             *     dev-package-names: string[]|null
             * } $content
             */
            $content = FileUtils::decodeJson($path);
            $runtimePackages = $content['packages'] ?? [];
            $debugPackages = $content['dev-package-names'] ?? [];

            return $this->parsePackages($runtimePackages, $debugPackages);
        });
    }

    /**
     * Gets all routes.
     *
     * @return RoutesType
     */
    private function getRoutes(): array
    {
        return $this->cache->get('routes', function (): array {
            /** @psalm-var array<string, RouteType> $runtimeRoutes */
            $runtimeRoutes = [];
            /** @psalm-var array<string, RouteType> $debugRoutes */
            $debugRoutes = [];
            $routes = $this->router->getRouteCollection()->all();
            foreach ($routes as $name => $route) {
                if ($this->isDebugRoute($name)) {
                    $debugRoutes[$name] = $this->parseRoute($name, $route);
                } else {
                    $runtimeRoutes[$name] = $this->parseRoute($name, $route);
                }
            }
            \ksort($runtimeRoutes);
            \ksort($debugRoutes);

            return [
                self::KEY_RUNTIME => $runtimeRoutes,
                self::KEY_DEBUG => $debugRoutes,
            ];
        });
    }

    private function isDebugRoute(string $name): bool
    {
        return \str_starts_with($name, '_');
    }

    private function isExtensionLoaded(string $extension, string $enabled = ''): bool
    {
        if (!\extension_loaded($extension)) {
            return false;
        }

        return '' === $enabled || \filter_var(\ini_get($enabled), \FILTER_VALIDATE_BOOLEAN);
    }

    private function makePathRelative(string $endPath, string $startPath): string
    {
        return \rtrim(FileUtils::makePathRelative($endPath, $startPath), '/src');
    }

    /**
     * @psalm-param array<string, PackageSourceType> $runtimePackages
     * @psalm-param string[]                         $debugPackages
     *
     * @psalm-return PackagesType
     */
    private function parsePackages(array $runtimePackages, array $debugPackages): array
    {
        $result = $this->getEmptyPackages();
        if ([] === $runtimePackages && [] === $debugPackages) {
            return $result;
        }

        foreach ($runtimePackages as $package) {
            $name = $package['name'];
            $entry = [
                'name' => $name,
                'license' => $this->getLicense($package),
                'version' => \ltrim($package['version'], 'v'),
                'description' => $this->cleanDescription($package['description'] ?? ''),
                'homepage' => $package['homepage'] ?? $package['support']['source'] ?? null,
            ];
            $type = \in_array($name, $debugPackages, true) ? self::KEY_DEBUG : self::KEY_RUNTIME;
            $result[$type][$name] = $entry;
        }
        \ksort($result[self::KEY_RUNTIME]);
        \ksort($result[self::KEY_DEBUG]);

        return $result;
    }

    /**
     * @psalm-return RouteType
     */
    private function parseRoute(string $name, Route $route): array
    {
        $methods = $route->getMethods();

        return [
            'name' => $name,
            'path' => $route->getPath(),
            'methods' => [] === $methods ? 'ANY' : \implode('|', $methods),
        ];
    }
}
