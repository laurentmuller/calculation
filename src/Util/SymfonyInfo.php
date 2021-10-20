<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Util;

use App\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Utility class to get Symfony informations.
 *
 * @author Laurent Muller
 *
 * @see https://github.com/symfony/symfony/blob/5.x/src/Symfony/Bundle/FrameworkBundle/Command/AboutCommand.php
 * @see https://github.com/EasyCorp/easy-doc-bundle/blob/master/src/Command/DocCommand.php
 *
 * @internal
 */
final class SymfonyInfo
{
    /**
     * The properties to get for a package.
     */
    private const PACKAGE_PROPERTIES = [
        'name',
        'version',
        'description',
        'homepage',
        //'type',
        // 'keywords',
        // 'authors',
        // 'license',
        // 'source',
        // 'bin',
        // 'autoload',
        // 'time'
    ];

    private KernelInterface $kernel;

    private ?array $packages = null;

    private RouterInterface $router;

    private ?array $routes = null;

    /**
     * Constructor.
     */
    public function __construct(KernelInterface $kernel, RouterInterface $router)
    {
        $this->kernel = $kernel;
        $this->router = $router;
    }

    /**
     * Gets bundles informations.
     */
    public function getBundles(): array
    {
        $bundles = [];
        $rootDir = \realpath($this->kernel->getProjectDir()) . \DIRECTORY_SEPARATOR;
        foreach ($this->kernel->getBundles() as $key => $bundleObject) {
            $bundles[$key] = [
                'name' => $key,
                'namespace' => $bundleObject->getNamespace(),
                'path' => \str_replace($rootDir, '', $bundleObject->getPath()),
            ];
        }

        // sort
        \ksort($bundles);

        return $bundles;
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
     * Gets packages informations.
     */
    public function getPackages(): array
    {
        if (null === $this->packages) {
            $result = [];
            $path = $this->kernel->getProjectDir() . '/composer.lock';
            if (FileUtils::exists($path)) {
                try {
                    // parse
                    $content = FileUtils::decodeJson($path);

                    // runtime packages
                    $result = [
                        'runtime' => $this->processPackages($content['packages'], false),
                    ];

                    //development packages
                    if ($this->isDebug()) {
                        $result['debug'] = $this->processPackages($content['packages-dev'], true);
                    }
                } catch (\InvalidArgumentException $e) {
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
     */
    public function getRoutes(): array
    {
        if (null === $this->routes) {
            $result = [];
            $routes = $this->router->getRouteCollection()->all();
            foreach ($routes as $name => $route) {
                $item = [
                    'name' => $name,
                    'path' => $route->getPath(),
                ];
                if (str_starts_with($name, '_')) {
                    $result['debug'][$name] = $item;
                } else {
                    $result['runtime'][$name] = $item;
                }
            }

            // sort
            if (!empty($result['runtime'])) {
                \ksort($result['runtime']);
            }
            if (!empty($result['debug'])) {
                \ksort($result['debug']);
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
     * Returns if the 'Zend OPcache' extension is loaded and enabled.
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
        $datetime = \DateTime::createFromFormat('d/m/Y', '01/' . $date);
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
     * @param string      $path    the path
     * @param string|null $baseDir the root path
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

    /**
     * Process the given packages.
     *
     * @param array $packages the packages to process
     * @param bool  $isDev    true if packages are requiered onyl for development mode
     *
     * @return string[][]
     */
    private function processPackages(array $packages, bool $isDev): array
    {
        $result = [];
        foreach ($packages as $entry) {
            $package = [];
            $package['dev'] = $isDev;
            foreach (self::PACKAGE_PROPERTIES as $key) {
                if ('version' === $key) {
                    $entry[$key] = \ltrim($entry[$key], 'v');
                } elseif ('description' === $key) {
                    $value = $entry[$key];
                    if ($value && !Utils::endwith($value, '.')) {
                        $entry[$key] .= '.';
                    }
                }
                $package[$key] = $entry[$key] ?? '';
            }
            $result[$package['name']] = $package;
        }

        // sort
        \ksort($result);

        return $result;
    }
}
