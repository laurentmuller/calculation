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
     * The build-in routes.
     */
    private const BUILT_IN_ROUTES = [
        '_profiler',
        '_profiler_exception',
        '_profiler_exception_css',
        '_profiler_home',
        '_profiler_info',
        '_profiler_open_file',
        '_profiler_phpinfo',
        '_profiler_router',
        '_profiler_search',
        '_profiler_search_bar',
        '_profiler_search_results',
        '_twig_error_test',
        '_wdt',
    ];

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
     * Formats the given path within the given base path.
     *
     * @param string      $path    the path
     * @param string|null $baseDir the root path
     *
     * @return string the relative path
     */
    public function formatPath(string $path, ?string $baseDir = null): string
    {
        $path = \str_replace('\\', '/', $path);
        if (null !== $baseDir) {
            $baseDir = \str_replace('\\', '/', $baseDir);

            return (string) \preg_replace('~^' . \preg_quote($baseDir, '~') . '~', '.', $path);
        }

        return $path;
    }

    /**
     * Gets bundles informations.
     */
    public function getBundles(): array
    {
        $bundles = [];
        $rootDir = \realpath($this->kernel->getProjectDir() . '/..') . \DIRECTORY_SEPARATOR;
        foreach ($this->kernel->getBundles() as $key => $bundleObject) {
            $bundle = [
                'name' => $key,
                'namespace' => $bundleObject->getNamespace(),
                'path' => \str_replace($rootDir, '', $bundleObject->getPath()),
            ];
            $bundles[$key] = $bundle;
        }

        // sort
        if (!empty($bundle)) {
            \ksort($bundles);
        }

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
     * Gets PHP informations as array.
     * Note:
     * <ul>
     * <li>'yes', 'enabled' and 'on' values are converted to boolean true.</li>
     * <li>'no', 'disabled' and'off' values are converted to boolean false.</li>
     * <li>if applicable values are converted to integer or float.</li>
     * </ul>.
     */
    public function getPhpInfoArray(): array
    {
        $content = $this->getPhpInfoText(\INFO_MODULES);

        $content = \strip_tags($content, '<h2><th><td>');
        $content = (string) \preg_replace('/<th[^>]*>([^<]+)<\/th>/', '<info>\1</info>', $content);
        $content = (string) \preg_replace('/<td[^>]*>([^<]+)<\/td>/', '<info>\1</info>', $content);
        $array = (array) \preg_split('/(<h2[^>]*>[^<]+<\/h2>)/', $content, -1, \PREG_SPLIT_DELIM_CAPTURE);

        $result = [];
        $count = \count($array);

        $regexInfo = '<info>([^<]+)<\/info>';
        $regex3cols = '/' . $regexInfo . '\s*' . $regexInfo . '\s*' . $regexInfo . '/';
        $regex2cols = '/' . $regexInfo . '\s*' . $regexInfo . '/';

        $matchs = null;
        $directive1 = null;
        $directive2 = null;
        for ($i = 1; $i < $count; ++$i) {
            if (\preg_match('/<h2[^>]*>([^<]+)<\/h2>/', (string) $array[$i], $matchs)) {
                $name = \trim($matchs[1]);
                $vals = \explode("\n", (string) $array[$i + 1]);
                foreach ($vals as $val) {
                    if (\preg_match($regex3cols, $val, $matchs)) { // 3 columns
                        $match1 = \trim($matchs[1]);
                        $match2 = $this->convert(\trim($matchs[2]));
                        $match3 = $this->convert(\trim($matchs[3]));

                        // special case for 'Directive'
                        if (0 === \strcasecmp('directive', $match1)) {
                            $directive1 = $match2;
                            $directive2 = $match3;
                        } elseif ($directive1 && $directive2) {
                            $result[$name][$match1] = [
                                $directive1 => $match2,
                                $directive2 => $match3,
                            ];
                        } else {
                            $result[$name][$match1] = [$match2,  $match3];
                        }
                    } elseif (\preg_match($regex2cols, $val, $matchs)) { // 2 columns
                        $match1 = \trim($matchs[1]);
                        $match2 = $this->convert(\trim($matchs[2]));
                        $result[$name][$match1] = $match2;
                        $directive1 = $directive2 = null;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Gets PHP informations as HTML.
     */
    public function getPhpInfoHtml(): string
    {
        // get info
        $info = $this->getPhpInfoText();

        // extract body
        $info = (string) \preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $info);

        // remove links
        $info = (string) \preg_replace('/<a\s(.+?)>(.+?)<\/a>/is', '<p>$2</p>', $info);

        // remove sensitive informations
        $info = (string) \preg_replace('/<tr>.*KEY.*<\/tr>/m', '', $info);
        $info = (string) \preg_replace('/<tr>.*MAILER_DSN.*<\/tr>/m', '', $info);
        $info = (string) \preg_replace('/<tr>.*DATABASE_URL.*<\/tr>/m', '', $info);
        $info = (string) \preg_replace('/<tr>.*DATABASE_EDIT.*<\/tr>/m', '', $info);
        $info = (string) \preg_replace('/<tr>.*PASSWORD.*<\/tr>/m', '', $info);

        // replace version
        $info = (string) \str_replace('PHP Version', 'Version', $info);

        // update table class
        $info = (string) \str_replace('<table>', "<table class='table table-hover table-sm mb-0'>", $info);

        return $info;
    }

    /**
     * Gets PHP informations as text (raw data).
     *
     * @param int $what The output may be customized by passing one or more of the following constants bitwise values summed
     *                  together in the optional what parameter.
     *                  One can also combine the respective constants or bitwise values
     *                  together with the bitwise or operator.
     */
    public function getPhpInfoText(int $what = \INFO_ALL): string
    {
        \ob_start();
        \phpinfo($what);
        $content = (string) \ob_get_contents();
        \ob_end_clean();

        return $content;
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
            $collection = $this->router->getRouteCollection();
            foreach ($collection->all() as $name => $routeObject) {
                $route = [
                    'name' => $name,
                    'path' => $routeObject->getPath(),
                    'php_class' => \get_class($routeObject),
                ];

                if (\in_array($name, self::BUILT_IN_ROUTES, true)) {
                    $result['debug'][$name] = $route;
                } else {
                    $result['runtime'][$name] = $route;
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
     * Converts the given variable.
     *
     * @param mixed $var the variable to convert
     *
     * @return mixed the converted variable
     */
    private function convert($var)
    {
        $value = \strtolower((string) $var);
        if (\in_array($value, ['yes', 'enabled', 'on', '1'], true)) {
            return true;
        } elseif (\in_array($value, ['no', 'disabled', 'off', '0'], true)) {
            return false;
        } elseif (\is_int($var) || \preg_match('/^-?\d+$/', $value)) {
            return (int) $value;
        } elseif (\is_float($var)) {
            return $var;
        } elseif (\preg_match('/^-?\d+\.\d+$/', $value)) {
            $pos = \strrpos($value, '.');
            $decimals = \strlen($value) - $pos - 1;

            return \round((float) $value, $decimals);
        } elseif ('no value' === $value) {
            return 'No value';
        } else {
            return \str_replace('\\', '/', $var);
        }
    }

    /**
     * Gets the number of days before expiration.
     *
     * @param string $date the date to get for
     *
     * @return string the number of days
     */
    private function formatDays(string $date): string
    {
        $datetime = \DateTime::createFromFormat('d/m/Y', '01/' . $date);
        if (false !== $datetime) {
            return (new \DateTime())->diff($datetime->modify('last day of this month 23:59:59'))->format('%R%a days');
        }

        return '';
    }

    /**
     * Format the expired date.
     *
     * @param string $date the date to format
     *
     * @return string the formatted date, if applicable; 'Unknown' otherwise
     */
    private function formatExpired(string $date): string
    {
        $datetime = \DateTime::createFromFormat('m/Y', $date);
        if (false !== $datetime) {
            return (string) FormatUtils::formatDate($datetime->modify('last day of this month 23:59:59'));
        }

        return 'Unknown';
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
