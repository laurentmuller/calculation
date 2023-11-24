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

use App\Utils\FileUtils;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Utility class to get Symfony information.
 *
 * @see https://github.com/symfony/symfony/blob/5.x/src/Symfony/Bundle/FrameworkBundle/Command/AboutCommand.php
 * @see https://github.com/EasyCorp/easy-doc-bundle/blob/master/src/Command/DocCommand.php
 *
 * @psalm-type RouteType = array{name: string, path: string}
 * @psalm-type PackageType = array{name: string, version: string, description: string, homepage: string}
 * @psalm-type BundleType = array{name: string, namespace: string, path: string, package: string, homepage?: string}
 *
 * @internal
 */
final class SymfonyInfoService
{
    /**
     * The array key for debug packages and routes.
     */
    public const KEY_DEBUG = 'debug';

    /**
     * The array key for runtime packages and routes.
     */
    public const KEY_RUNTIME = 'runtime';

    /**
     * The file name containing composer information.
     */
    private const PACKAGE_FILE_NAME = '/vendor/composer/installed.json';

    /**
     * The package properties.
     */
    private const PACKAGE_PROPERTIES = [
        'name',
        'version',
        'description',
        'homepage',
    ];

    /**
     * The unknown label.
     */
    private const UNKNOWN = 'Unknown';

    /**
     * The bundles.
     *
     * @var array<string, BundleType>|null
     */
    private ?array $bundles = null;

    /**
     * The packages.
     *
     * @var null|array{
     *     runtime?: array<string, PackageType>,
     *     debug?: array<string, PackageType>
     * }
     */
    private ?array $packages = null;

    /**
     * The project directory.
     */
    private readonly string $projectDir;

    /**
     * The routes.
     *
     * @var null|array{
     *     runtime?: array<string, RouteType>,
     *     debug?: array<string, RouteType>
     * }
     */
    private ?array $routes = null;

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly RouterInterface $router,
        private readonly CacheItemPoolInterface $cache,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
    ) {
        $this->projectDir = FileUtils::normalizeDirectory($projectDir);
    }

    /**
     * Gets bundles information.
     *
     * @return array<string, BundleType>
     */
    public function getBundles(): array
    {
        if (null !== $this->bundles) {
            return $this->bundles;
        }

        $this->bundles = [];
        $projectDir = $this->projectDir;
        $vendorDir = FileUtils::buildPath($projectDir, 'vendor');
        foreach ($this->kernel->getBundles() as $key => $bundleObject) {
            $path = $bundleObject->getPath();
            $this->bundles[$key] = [
                'name' => $key,
                'namespace' => $bundleObject->getNamespace(),
                'path' => $this->makePathRelative($path, $projectDir),
                'package' => $this->makePathRelative($path, $vendorDir),
            ];
        }
        if ([] !== $this->bundles) {
            \ksort($this->bundles);
            $this->getPackages();
            $this->updateBundles();
        }

        return $this->bundles; /* @phpstan-ignore-line */
    }

    /**
     * Gets the cache directory path.
     */
    public function getCacheDir(): string
    {
        return $this->formatPath($this->kernel->getCacheDir(), $this->getProjectDir());
    }

    /**
     * Gets the cache directory path and the formatted size.
     */
    public function getCacheInfo(): string
    {
        return "{$this->getCacheDir()} ({$this->getCacheSize()})";
    }

    /**
     * Gets the formatted size of the cache directory.
     */
    public function getCacheSize(): string
    {
        return FileUtils::formatSize($this->kernel->getCacheDir());
    }

    /**
     * Gets the charset of the application.
     */
    public function getCharset(): string
    {
        return $this->kernel->getCharset();
    }

    /**
     * Gets the end of life.
     */
    public function getEndOfLife(): string
    {
        return $this->formatMonthYear(Kernel::END_OF_LIFE);
    }

    /**
     * Gets the end of maintenance.
     */
    public function getEndOfMaintenance(): string
    {
        return $this->formatMonthYear(Kernel::END_OF_MAINTENANCE);
    }

    /**
     * Gets the kernel environment.
     */
    public function getEnvironment(): string
    {
        return $this->kernel->getEnvironment();
    }

    /**
     * Gets the log directory path.
     */
    public function getLogDir(): string
    {
        return $this->formatPath($this->kernel->getLogDir(), $this->getProjectDir());
    }

    /**
     * Gets the log directory path and the formatted size.
     */
    public function getLogInfo(): string
    {
        return "{$this->getLogDir()} ({$this->getLogSize()})";
    }

    /**
     * Gets the formatted size of the log directory.
     */
    public function getLogSize(): string
    {
        return FileUtils::formatSize($this->kernel->getLogDir());
    }

    /**
     * Gets the maintenance status.
     */
    public function getMaintenanceStatus(): string
    {
        $now = new \DateTimeImmutable();
        $eol = $this->getEndOfMonth(Kernel::END_OF_LIFE);
        $eom = $this->getEndOfMonth(Kernel::END_OF_MAINTENANCE);
        if ($eom instanceof \DateTimeImmutable && $eol instanceof \DateTimeImmutable) {
            if ($now > $eol) {
                return 'Unmaintained';
            }
            if ($now > $eom) {
                return 'Security Fixes Only';
            }

            return 'Maintained';
        }

        return self::UNKNOWN;
    }

    /**
     * Gets packages information.
     *
     * @return array{
     *     runtime?: array<string, PackageType>,
     *     debug?: array<string, PackageType>
     * }
     */
    public function getPackages(): array
    {
        if (null === $this->packages) {
            $this->packages = [];
            $path = $this->projectDir . self::PACKAGE_FILE_NAME;
            if (FileUtils::exists($path)) {
                try {
                    /**
                     * @var array{
                     *     packages: array<string, PackageType>,
                     *     'dev-package-names': string[]|null
                     * } $content
                     */
                    $content = FileUtils::decodeJson($path);
                    $runtimePackages = $content['packages'];
                    $debugPackages = $content['dev-package-names'] ?? [];
                    $this->packages = $this->processPackages($runtimePackages, $debugPackages);
                } catch (\InvalidArgumentException) {
                    // ignore
                }
            }
        }

        return $this->packages;
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
        $date = $this->loadReleaseDate();
        if (\is_string($date)) {
            return $this->formatMonthYear($date);
        }

        return self::UNKNOWN;
    }

    /**
     * Gets all routes.
     *
     * @return array{
     *     runtime?: array<string, RouteType>,
     *     debug?: array<string, RouteType>
     * }
     */
    public function getRoutes(): array
    {
        if (null === $this->routes) {
            $result = [];
            $routes = $this->router->getRouteCollection()->all();
            foreach ($routes as $name => $route) {
                $key = $this->isDebugRoute($name) ? self::KEY_DEBUG : self::KEY_RUNTIME;
                $result[$key][$name] = [
                    'name' => $name,
                    'path' => $route->getPath(),
                ];
            }
            if (!empty($result[self::KEY_RUNTIME])) {
                \ksort($result[self::KEY_RUNTIME]);
            }
            if (!empty($result[self::KEY_DEBUG])) {
                \ksort($result[self::KEY_DEBUG]);
            }
            $this->routes = $result;
        }

        return $this->routes;
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
        return \extension_loaded('apcu') && \filter_var(\ini_get('apc.enabled'), \FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Gets if debug mode is enabled.
     */
    public function isDebug(): bool
    {
        return $this->kernel->isDebug();
    }

    /**
     * Returns if the 'xdebug' extension is loaded.
     */
    public function isXdebugLoaded(): bool
    {
        return \extension_loaded('xdebug');
    }

    /**
     * Returns if the 'Zend OP cache' extension is loaded and enabled.
     */
    public function isZendCacheLoaded(): bool
    {
        return \extension_loaded('Zend OPcache') && \filter_var(\ini_get('opcache.enable'), \FILTER_VALIDATE_BOOLEAN);
    }

    private function cleanDescription(string $description): string
    {
        if ('' !== $description && !\str_ends_with($description, '.')) {
            return $description . '.';
        }

        return $description;
    }

    private function cleanVersion(string $version): string
    {
        return \ltrim($version, 'v');
    }

    /**
     * @psalm-return PackageType|null
     */
    private function findPackage(string $name): ?array
    {
        return $this->packages['runtime'][$name] ?? $this->packages['debug'][$name] ?? null;
    }

    /**
     * Format a date.
     *
     * @param string $date the date (month/year) to format
     *
     * @return string the formatted date, if applicable; 'Unknown' otherwise
     */
    private function formatMonthYear(string $date): string
    {
        $date = \DateTimeImmutable::createFromFormat('d/m/Y', '01/' . $date);
        if ($date instanceof \DateTimeImmutable) {
            return $date->format('F Y');
        }

        return self::UNKNOWN;
    }

    /**
     * Formats the given path within the given optional base path.
     *
     * @param string  $path    the path
     * @param ?string $baseDir the base (root) path
     *
     * @return string the relative path
     */
    private function formatPath(string $path, string $baseDir = null): string
    {
        if (null !== $baseDir) {
            try {
                return FileUtils::makePathRelative($path, $baseDir, true);
            } catch (\InvalidArgumentException) {
            }
        }

        return FileUtils::normalizeDirectory($path);
    }

    /**
     * Gets the end of month date.
     *
     * @param string $value the date as month/year format
     *
     * @return \DateTimeImmutable|false the date or false
     */
    private function getEndOfMonth(string $value): \DateTimeImmutable|false
    {
        $date = \DateTimeImmutable::createFromFormat('d/m/Y', '01/' . $value);
        if ($date instanceof \DateTimeImmutable) {
            return $date->modify('last day of this month');
        }

        return $date;
    }

    private function isDebugRoute(string $name): bool
    {
        return \str_starts_with($name, '_');
    }

    /**
     * Load the release date.
     */
    private function loadReleaseDate(): ?string
    {
        try {
            $item = $this->cache->getItem('symfony_release_date');
            if ($item->isHit()) {
                return (string) $item->get();
            }
            $url = \sprintf('https://symfony.com/releases/%s.%s.json', Kernel::MAJOR_VERSION, Kernel::MINOR_VERSION);
            $releases = FileUtils::decodeJson($url);
            if (isset($releases['release_date']) && \is_string($releases['release_date'])) {
                $value = $releases['release_date'];
                $item->set($value);
                $this->cache->save($item);

                return $value;
            }
        } catch (\Psr\Cache\InvalidArgumentException|\InvalidArgumentException) {
            // ignore
        }

        return null;
    }

    private function makePathRelative(string $endPath, string $startPath): string
    {
        return \rtrim(FileUtils::makePathRelative($endPath, $startPath), '/src');
    }

    /**
     * @param array<string, PackageType> $runtimePackages
     * @param string[]                   $debugPackages
     *
     * @return array{
     *          runtime?: array<string, PackageType>,
     *          debug?: array<string, PackageType>
     *          }
     */
    private function processPackages(array $runtimePackages, array $debugPackages): array
    {
        $result = [];
        foreach ($runtimePackages as $package) {
            $name = $package['name'];
            $entry = ['name' => $name];
            foreach (self::PACKAGE_PROPERTIES as $key) {
                $value = $package[$key] ?? '';
                switch ($key) {
                    case 'description':
                        $value = $this->cleanDescription($value);
                        break;
                    case 'version':
                        $value = $this->cleanVersion($value);
                        break;
                }
                $entry[$key] = $value;
            }
            $type = \in_array($name, $debugPackages, true) ? self::KEY_DEBUG : self::KEY_RUNTIME;
            $result[$type][$name] = $entry;
        }
        if (!empty($result[self::KEY_RUNTIME])) {
            \ksort($result[self::KEY_RUNTIME]);
        }
        if (!empty($result[self::KEY_DEBUG])) {
            \ksort($result[self::KEY_DEBUG]);
        }

        return $result; // @phpstan-ignore-line
    }

    private function updateBundles(): void
    {
        if (null !== $this->bundles && null !== $this->packages) {
            foreach ($this->bundles as &$bundle) {
                $package = $this->findPackage($bundle['package']);
                if (null !== $package) {
                    $bundle['homepage'] = $package['homepage'];
                }
            }
        }
    }
}
