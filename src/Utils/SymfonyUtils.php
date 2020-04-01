<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Utils;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Utility class to get Symfony informations.
 *
 * @author Laurent Muller
 *
 * @see https://github.com/EasyCorp/easy-doc-bundle/blob/master/src/Command/DocCommand.php
 *
 * @internal
 */
final class SymfonyUtils
{
    /**
     * The build-in routes.
     *
     * @var array
     */
    private static $BUILT_IN_ROUTES = [
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
     * The names of properties to get for a package.
     *
     * @var array
     */
    private static $PACKAGE_PROPERTIES = [
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

    // prevent instance creation
    private function __construct()
    {
        // no-op
    }

    /**
     * Formats the size of the given path.
     *
     * @param string $path the file or directory path
     *
     * @return string the formatted size
     */
    public static function formatFileSize(string $path): string
    {
        if (\is_file($path)) {
            $size = \filesize($path) ?: 0;
        } else {
            $size = 0;
            $flags = \RecursiveDirectoryIterator::SKIP_DOTS;
            $innerIterator = new \RecursiveDirectoryIterator($path, $flags);
            $outerIterator = new \RecursiveIteratorIterator($innerIterator);
            foreach ($outerIterator as $file) {
                $size += $file->getSize();
            }
        }

        if (0 === $size) {
            return 'empty';
        }

        $sizes = [
            1073741824 => '%.1f GB',
            1048576 => '%.1f MB',
            1024 => '%.0f KB',
            0 => '%.0f B',
        ];

        foreach ($sizes as $minSize => $format) {
            if ($size >= $minSize) {
                $value = 0 !== $minSize ? $size / $minSize : $size;

                return \sprintf($format, $value);
            }
        }

        // must never reached
        return 'unknown';
    }

    /**
     * Formats the given path within the give base path.
     *
     * @param string      $path    the path
     * @param string|null $baseDir the root path
     *
     * @return string the relative path
     */
    public static function formatPath(string $path, ?string $baseDir = null): string
    {
        $path = \str_replace('\\', '/', $path);
        if ($baseDir) {
            $baseDir = \str_replace('\\', '/', $baseDir);

            return \preg_replace('~^' . \preg_quote($baseDir, '~') . '~', '.', $path);
        }

        return  $path;
    }

    /**
     * Gets bundles informations.
     *
     * @param kernel $kernel the kernel to get bundles for
     */
    public static function getBundles(KernelInterface $kernel): array
    {
        $bundles = [];
        $rootDir = \realpath($kernel->getProjectDir() . '/..') . \DIRECTORY_SEPARATOR;
        foreach ($kernel->getBundles() as $key => $bundleObject) {
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
     * Gets the formatted size of the cache directory.
     *
     * @param KernelInterface $kernel the kernel used to get the cache directory
     *
     * @return string the formatted size
     */
    public static function getCacheSize(KernelInterface $kernel): string
    {
        $dir = $kernel->getCacheDir();

        return self::formatFileSize($dir);
    }

    /**
     * Gets packages informations.
     *
     * @param kernel $kernel the kernel to get packages for
     */
    public static function getPackages(KernelInterface $kernel): array
    {
        // get file
        $path = $kernel->getProjectDir() . '/composer.lock';
        if (!\file_exists($path)) {
            return [];
        }

        // parse
        $content = \json_decode(\file_get_contents($path), true);
        $packages = self::processPackages($content['packages']);
        if ($kernel->isDebug()) {
            $devPackages = self::processPackages($content['packages-dev'], true);
            $packages = \array_merge($packages, $devPackages);
        }

        // sort
        \ksort($packages);

        return $packages;
    }

    /**
     * Gets PHP informations as array.
     * Note:
     * <ul>
     * <li>'yes', 'enabled' and 'on' values are converted to boolean true.</li>
     * <li>'no', 'disabled' and'off' values are converted to boolean false.</li>
     * <li>if applicable values are converted to integer or float.</li>
     * </ul>
     */
    public static function getPhpInfoArray(): array
    {
        $content = self::getPhpInfoText(INFO_MODULES);

        $content = \strip_tags($content, '<h2><th><td>');
        $content = \preg_replace('/<th[^>]*>([^<]+)<\/th>/', '<info>\1</info>', $content);
        $content = \preg_replace('/<td[^>]*>([^<]+)<\/td>/', '<info>\1</info>', $content);
        $array = \preg_split('/(<h2[^>]*>[^<]+<\/h2>)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);

        $result = [];
        $count = \count($array);

        $regexInfo = '<info>([^<]+)<\/info>';
        $regex3cols = '/' . $regexInfo . '\s*' . $regexInfo . '\s*' . $regexInfo . '/';
        $regex2cols = '/' . $regexInfo . '\s*' . $regexInfo . '/';

        $matchs = null;
        for ($i = 1; $i < $count; ++$i) {
            if (\preg_match('/<h2[^>]*>([^<]+)<\/h2>/', $array[$i], $matchs)) {
                $name = \trim($matchs[1]);
                $vals = \explode("\n", $array[$i + 1]);
                foreach ($vals as $val) {
                    if (\preg_match($regex3cols, $val, $matchs)) { // 3 columns
                        $match1 = \trim($matchs[1]);
                        $match2 = self::convert(\trim($matchs[2]));
                        $match3 = self::convert(\trim($matchs[3]));

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
                        $match2 = self::convert(\trim($matchs[2]));
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
    public static function getPhpInfoHtml(): string
    {
        // get info
        $info = self::getPhpInfoText();

        // extract body
        $info = \preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $info);

        // remove links
        $info = \preg_replace('/<a\s(.+?)>(.+?)<\/a>/is', '<p>$2</p>', $info);

        // replace version
        $info = \str_replace('PHP Version', 'Version', $info);

        // update table class
        return \str_replace('<table>', "<table class='table table-hover table-sm mb-0'>", $info);
    }

    /**
     * Gets PHP informations as text (raw data).
     *
     * @param int $what The output may be customized by passing one or more of the following constants bitwise values summed
     *                  together in the optional what parameter.
     *                  One can also combine the respective constants or bitwise values
     *                  together with the bitwise or operator.
     */
    public static function getPhpInfoText(int $what = INFO_ALL): string
    {
        \ob_start();
        \phpinfo($what);
        $content = \ob_get_contents();
        \ob_end_clean();

        return $content;
    }

    /**
     * Gets all routes.
     *
     * @param routerInterface $router the router
     */
    public static function getRoutes(RouterInterface $router): array
    {
        $result = [];
        $collection = $router->getRouteCollection();
        foreach ($collection->all() as $name => $routeObject) {
            $route = [
                'name' => $name,
                'path' => $routeObject->getPath(),
                'php_class' => \get_class($routeObject),
            ];

            //if (strpos('_', $name) === 0) {}
            if (\in_array($name, self::$BUILT_IN_ROUTES, true)) {
                $result['symfony'][$name] = $route;
            } else {
                $result['application'][$name] = $route;
            }
        }

        // sort
        if (!empty($result['symfony'])) {
            \ksort($result['symfony']);
        }
        if (!empty($result['application'])) {
            \ksort($result['application']);
        }

        return $result;
    }

    /**
     * Gets MySQL configuration.
     *
     * @return array an array with each row containing 2 columns ('Variable_name' and 'Value)
     */
    public static function getSqlConfiguration(EntityManagerInterface $manager): array
    {
        $result = [];

        try {
            $sql = 'SHOW VARIABLES';

            /** @var \Doctrine\DBAL\Connection $connection */
            $connection = $manager->getConnection();

            /** @var \PDOStatement $statement */
            $statement = $connection->prepare($sql);

            if ($statement->execute()) {
                $values = $statement->fetchAll();
                $statement->closeCursor();

                return \array_filter($values, function ($key) {
                    return 0 !== \strlen($key['Value']);
                });
            }
        } catch (\Exception $e) {
            // ignore
        }

        return $result;
    }

    /**
     * Gets the database configuration.
     *
     * @return array the database server informations
     */
    public static function getSqlDatabase(EntityManagerInterface $manager): array
    {
        $result = [];

        try {
            $params = $manager->getConnection()->getParams();
            foreach (['dbname', 'host', 'port', 'driver'] as $key) {
                $result[$key] = $params[$key] ?? null;
            }

            return \array_filter($result, function ($value) {
                return Utils::isString($value);
            });
        } catch (\Exception $e) {
            // ignore
        }

        return $result;
    }

    /**
     * Gets MySQL server version.
     *
     * @return string the server version or "<code>Unknown</code>" if an error occurs
     */
    public static function getSqlVersion(EntityManagerInterface $manager): string
    {
        try {
            $sql = 'SHOW VARIABLES LIKE "version"';

            /** @var \Doctrine\DBAL\Connection $connection */
            $connection = $manager->getConnection();

            /** @var \PDOStatement $statement */
            $statement = $connection->prepare($sql);

            if ($statement->execute()) {
                $result = $statement->fetch();
                $statement->closeCursor();

                if (false !== $result) {
                    return $result['Value'];
                }
            }
        } catch (\Exception $e) {
            // ignore
        }

        return 'Unknown';
    }

    private static function convert($var)
    {
        $value = \strtolower((string) $var);
        if (\in_array($value, ['yes', 'enabled', 'on'], true)) {
            return true;
        } elseif (\in_array($value, ['no', 'disabled', 'off'], true)) {
            return false;
        } elseif (\is_int($var) || \preg_match('/^-?\d+$/', (string) $var)) {
            return (int) $var;
        } elseif (\is_float($var) || \preg_match('/^-?\d+\.\d+$/', (string) $var)) {
            return (float) $var;
        } else {
            return $var;
        }
    }

    /**
     * Process the given packages.
     *
     * @param array $packages the packages to process
     * @param bool  $isDev    true if packahes are requiered onyl for development mode
     *
     * @return string[][]
     */
    private static function processPackages(array $packages, $isDev = false): array
    {
        $result = [];
        foreach ($packages as $entry) {
            $package = [];
            $package['dev'] = $isDev;
            foreach (self::$PACKAGE_PROPERTIES as $key) {
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

        return $result;
    }
}
