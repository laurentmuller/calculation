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

use App\Util\FileUtils;
use App\Util\FormatUtils;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Utility class to get Symfony information.
 *
 * @see https://github.com/symfony/symfony/blob/5.x/src/Symfony/Bundle/FrameworkBundle/Command/AboutCommand.php
 * @see https://github.com/EasyCorp/easy-doc-bundle/blob/master/src/Command/DocCommand.php
 *
 * @internal
 */
final class SymfonyInfoService
{
    /**
     * The array key for debug packages and routes.
     */
    final public const KEY_DEBUG = 'debug';

    /**
     * The array key for runtime packages and routes.
     */
    final public const KEY_RUNTIME = 'runtime';

    private const PACKAGE_FILE_NAME = '/vendor/composer/installed.json';

    private const PACKAGE_PROPERTIES = [
        'name',
        'version',
        'description',
        'homepage',
    ];

    /**
     * @var array<string, array{name: string, namespace: string, path: string}>|null
     */
    private ?array $bundles = null;

    /**
     * @var null|array{
     *     runtime?: array<string, array{name: string, version: string, description: string, homepage: string}>,
     *     debug?: array<string, array{name: string, version: string, description: string, homepage: string}>
     * }
     */
    private ?array $packages = null;

    /**
     * @var null|array{
     *     runtime?: array<string, array{name: string, path: string}>,
     *     debug?: array<string, array{name: string, path: string}>
     * }
     */
    private ?array $routes = null;

    /**
     * Constructor.
     */
    public function __construct(private readonly KernelInterface $kernel, private readonly RouterInterface $router)
    {
    }

    /**
     * Gets bundles information.
     *
     * @return array<string, array{name: string, namespace: string, path: string}>
     */
    public function getBundles(): array
    {
        if (null === $this->bundles) {
            $this->bundles = [];
            $rootDir = \realpath($this->kernel->getProjectDir()) . \DIRECTORY_SEPARATOR;
            foreach ($this->kernel->getBundles() as $key => $bundleObject) {
                $this->bundles[$key] = [
                    'name' => $key,
                    'namespace' => $bundleObject->getNamespace(),
                    'path' => \str_replace($rootDir, '', $bundleObject->getPath()),
                ];
            }
            if (!empty($this->bundles)) {
                \ksort($this->bundles);
            }
        }

        return $this->bundles;
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
        return $this->formatExpired(Kernel::END_OF_LIFE);
    }

    /**
     * Gets the end of life in days.
     */
    public function getEndOfLifeDays(): string
    {
        return $this->formatDays(Kernel::END_OF_LIFE);
    }

    /**
     * Gets the end of life date and days.
     */
    public function getEndOfLifeInfo(): string
    {
        return "{$this->getEndOfLife()} ({$this->getEndOfLifeDays()})";
    }

    /**
     * Gets the end of maintenance.
     */
    public function getEndOfMaintenance(): string
    {
        return $this->formatExpired(Kernel::END_OF_MAINTENANCE);
    }

    /**
     * Gets the end of maintenance in days.
     */
    public function getEndOfMaintenanceDays(): string
    {
        return $this->formatDays(Kernel::END_OF_MAINTENANCE);
    }

    /**
     * Gets the end of maintenance date and days.
     */
    public function getEndOfMaintenanceInfo(): string
    {
        return "{$this->getEndOfMaintenance()} ({$this->getEndOfMaintenanceDays()})";
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
     * Gets packages information.
     *
     * @return array{
     *     runtime?: array<string, array{name: string, version: string, description: string, homepage: string}>,
     *     debug?: array<string, array{name: string, version: string, description: string, homepage: string}>
     * }
     */
    public function getPackages(): array
    {
        if (null === $this->packages) {
            $result = [];
            $path = $this->kernel->getProjectDir() . self::PACKAGE_FILE_NAME;
            if (FileUtils::exists($path)) {
                try {
                    /**
                     * @var array{
                     *     'dev-package-names': string[]|null,
                     *     packages: array<string, array{name: string, version: string, description?: string, homepage?: string}>
                     * } $content
                     */
                    $content = FileUtils::decodeJson($path);
                    $packages = $content['packages'];
                    $devPackageNames = $content['dev-package-names'] ?? [];
                    $result = $this->processPackages($packages, $devPackageNames);
                } catch (\InvalidArgumentException) {
                    // ignore
                }
            }
            $this->packages = $result;
        }

        return $this->packages;
    }

    /**
     * Gets the project directory path.
     */
    public function getProjectDir(): string
    {
        return \str_replace('\\', '/', $this->kernel->getProjectDir());
    }

    /**
     * Gets all routes.
     *
     * @return array{
     *     runtime?: array<string, array{name: string, path: string}>,
     *     debug?: array<string, array{name: string, path: string}>
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

    /**
     * Gets the number of days before expiration.
     *
     * @param string $date the date (month/year) to get for
     *
     * @return string the number of days, if applicable; '' otherwise
     */
    private function formatDays(string $date): string
    {
        $datetime = \DateTime::createFromFormat('d/m/Y', "01/$date");
        if ($datetime instanceof \DateTime) {
            return (new \DateTime())->diff($datetime->modify('last day of this month 23:59:59'))->format('%R%a days');
        }

        return '';
    }

    /**
     * Format the expired date.
     *
     * @param string $date the date (month/year) to format
     *
     * @return string the formatted date, if applicable; 'Unknown' otherwise
     */
    private function formatExpired(string $date): string
    {
        $datetime = \DateTime::createFromFormat('m/Y', $date);
        if ($datetime instanceof \DateTime) {
            return (string) FormatUtils::formatDate($datetime->modify('last day of this month 23:59:59'));
        }

        return 'Unknown';
    }

    /**
     * Formats the given path within the given base path.
     *
     * @param string  $path    the path
     * @param ?string $baseDir the root path
     *
     * @return string the relative path
     */
    private function formatPath(string $path, ?string $baseDir = null): string
    {
        $path = \str_replace('\\', '/', $path);
        if (null !== $baseDir) {
            $baseDir = \str_replace('\\', '/', $baseDir);

            return (string) \preg_replace('~^' . \preg_quote($baseDir, '~') . '~', '.', $path);
        }

        return $path;
    }

    private function isDebugRoute(string $name): bool
    {
        return \str_starts_with($name, '_');
    }

    /**
     * @param array<string, array{name: string, version: string, description?: string, homepage?: string}> $packages
     * @param string[]                                                                                     $devPackageNames
     *
     * @return  array{
     *          runtime?: array<string, array{name: string, version: string, description: string, homepage: string}>,
     *          debug?: array<string, array{name: string, version: string, description: string, homepage: string}>
     *          }
     */
    private function processPackages(array $packages, array $devPackageNames): array
    {
        $result = [];
        foreach ($packages as $package) {
            $name = $package['name'];
            $entry = ['name' => $name];
            $type = \in_array($name, $devPackageNames, true) ? self::KEY_DEBUG : self::KEY_RUNTIME;
            foreach (self::PACKAGE_PROPERTIES as $key) {
                $value = $package[$key] ?? '';
                switch ($key) {
                    case 'description':
                        if ('' !== $value && !\str_ends_with($value, '.')) {
                            $value .= '.';
                        }
                        break;
                    case 'version':
                        $value = \ltrim($value, 'v');
                        break;
                }
                $entry[$key] = $value;
            }
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
}
