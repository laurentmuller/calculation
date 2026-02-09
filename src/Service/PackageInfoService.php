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

use App\Constant\CacheAttributes;
use App\Utils\FileUtils;
use App\Utils\FormatUtils;
use App\Utils\StringUtils;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Filesystem\Path;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Service to get information about packages.
 *
 * @phpstan-type PackageType = array{
 *       name: string,
 *       version: string,
 *       description: string,
 *       homepage: string|null,
 *       license: string|null,
 *       time: string,
 *       debug: bool,
 *       production: array<string, string>,
 *       development: array<string, string>}
 * @phpstan-type PackageSourceType = array{
 *      name: string,
 *      version: string,
 *      description?: string,
 *      homepage?: string,
 *      time: string,
 *      support?: array{source?: string},
 *      require?: array<string, string>,
 *      require-dev?: array<string, string>}
 */
readonly class PackageInfoService implements \Countable
{
    private const string LICENSE_PATTERN = '{license{*},LICENSE{*}}';

    public function __construct(
        #[Autowire('%kernel.project_dir%/composer.lock')]
        private string $jsonPath,
        #[Autowire('%kernel.project_dir%/vendor')]
        private string $vendorPath,
        #[Target(CacheAttributes::CACHE_SYMFONY)]
        private CacheInterface $cache,
    ) {
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->getPackages());
    }

    /**
     * Gets the debug packages.
     *
     * @return array<string, PackageType>
     */
    public function getDebugPackages(): array
    {
        return $this->filterPackages(true);
    }

    /**
     * Gets the package for the given name.
     *
     * @phpstan-return PackageType
     *
     * @throws \InvalidArgumentException if the package name does not exist
     */
    public function getPackage(string $name): array
    {
        return $this->getPackages()[$name] ?? throw new \InvalidArgumentException(\sprintf('Unknown package name: "%s".', $name));
    }

    /**
     * @phpstan-return array<string, PackageType>
     */
    public function getPackages(): array
    {
        return $this->cache->get('packages', $this->loadPackages(...));
    }

    /**
     * Gets the runtime packages.
     *
     * @return array<string, PackageType>
     */
    public function getRuntimePackages(): array
    {
        return $this->filterPackages(false);
    }

    /**
     * Gets a value indicating if the package exists for the given name.
     */
    public function hasPackage(string $name): bool
    {
        return \array_key_exists($name, $this->getPackages());
    }

    /**
     * @phpstan-return array<string, PackageType>
     */
    private function filterPackages(bool $debug): array
    {
        return \array_filter($this->getPackages(), static fn (array $package): bool => $debug === $package['debug']);
    }

    /**
     * @phpstan-param PackageSourceType $package
     */
    private function getLicensePattern(array $package): string
    {
        return Path::join($this->vendorPath, $package['name'], self::LICENSE_PATTERN);
    }

    /**
     * @phpstan-return array<string, PackageType>
     */
    private function loadPackages(): array
    {
        /** @phpstan-var array{packages: PackageSourceType[], packages-dev: PackageSourceType[]} $content */
        $content = FileUtils::decodeJson($this->jsonPath);
        $packages = \array_merge(
            $this->parsePackages($content['packages'], false),
            $this->parsePackages($content['packages-dev'], true)
        );
        \ksort($packages);

        return $packages;
    }

    /**
     * @phpstan-param PackageSourceType $package
     */
    private function packageExists(array $package): bool
    {
        return \file_exists(Path::join($this->vendorPath, $package['name']));
    }

    /**
     * @phpstan-param PackageSourceType $package
     */
    private function parseDescription(array $package): string
    {
        $description = $package['description'] ?? '';

        return StringUtils::isString($description) ? \rtrim($description, '.') . '.' : '';
    }

    /**
     * @phpstan-param PackageSourceType $package
     *
     * @return array<string, string>
     */
    private function parseDevelopment(array $package): array
    {
        return $package['require-dev'] ?? [];
    }

    /**
     * @phpstan-param PackageSourceType $package
     */
    private function parseHomepage(array $package): ?string
    {
        return $package['homepage'] ?? $package['support']['source'] ?? null;
    }

    /**
     * @phpstan-param PackageSourceType $package
     */
    private function parseLicense(array $package): ?string
    {
        /** @var list<string> $files */
        $files = \glob($this->getLicensePattern($package), \GLOB_BRACE | \GLOB_NOSORT);

        return \array_first($files);
    }

    /**
     * @phpstan-param PackageSourceType $package
     *
     * @return PackageType
     */
    private function parsePackage(string $name, bool $debug, array $package): array
    {
        return [
            'name' => $name,
            'debug' => $debug,
            'time' => $this->parseTime($package),
            'version' => $this->parseVersion($package),
            'license' => $this->parseLicense($package),
            'homepage' => $this->parseHomepage($package),
            'description' => $this->parseDescription($package),
            'production' => $this->parseProduction($package),
            'development' => $this->parseDevelopment($package),
        ];
    }

    /**
     * @phpstan-param PackageSourceType[] $packages
     *
     * @phpstan-return array<string, PackageType>
     */
    private function parsePackages(array $packages, bool $debug): array
    {
        $results = [];
        foreach ($packages as $package) {
            if (!$this->packageExists($package)) {
                continue;
            }
            $name = $package['name'];
            $results[$name] = $this->parsePackage($name, $debug, $package);
        }

        return $results;
    }

    /**
     * @phpstan-param PackageSourceType $package
     *
     * @return array<string, string>
     */
    private function parseProduction(array $package): array
    {
        return $package['require'] ?? [];
    }

    /**
     * @phpstan-param PackageSourceType $package
     */
    private function parseTime(array $package): string
    {
        return FormatUtils::formatDate(new DatePoint($package['time']));
    }

    /**
     * @phpstan-param PackageSourceType $package
     */
    private function parseVersion(array $package): string
    {
        return \ltrim($package['version'], 'v');
    }
}
