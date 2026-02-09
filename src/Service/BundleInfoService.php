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

use App\Constants\CacheAttributes;
use App\Utils\FileUtils;
use App\Utils\FormatUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Service to get information about bundles.
 *
 * @phpstan-type BundleType = array{
 *      name: string,
 *      namespace: string,
 *      path: string,
 *      package: string,
 *      files: string,
 *      size: string}
 */
readonly class BundleInfoService
{
    public function __construct(
        private KernelInterface $kernel,
        #[Target(CacheAttributes::CACHE_SYMFONY)]
        private CacheInterface $cache,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    /**
     * Gets bundle's information.
     *
     * @phpstan-return array<string, BundleType>
     */
    public function getBundles(): array
    {
        return $this->cache->get('bundles', $this->loadBundles(...));
    }

    /**
     * @phpstan-return array<string, BundleType>
     */
    private function loadBundles(): array
    {
        $bundles = [];
        $vendorDir = Path::join($this->projectDir, 'vendor');
        foreach ($this->kernel->getBundles() as $name => $bundle) {
            $bundles[$name] = $this->parseBundle($name, $bundle, $vendorDir);
        }
        \ksort($bundles);

        return $bundles;
    }

    private function makePathRelative(string $endPath, ?string $startPath = null): string
    {
        return \rtrim(FileUtils::makePathRelative($endPath, $startPath ?? $this->projectDir), '/src');
    }

    /**
     * @phpstan-return BundleType
     */
    private function parseBundle(string $name, BundleInterface $bundle, string $vendorDir): array
    {
        $path = $bundle->getPath();
        $size = FileUtils::sizeAndFiles($path);

        return [
            'name' => $name,
            'namespace' => $bundle->getNamespace(),
            'path' => $this->makePathRelative($path),
            'package' => $this->makePathRelative($path, $vendorDir),
            'files' => FormatUtils::formatInt($size['files']),
            'size' => FileUtils::formatSize($size['size']),
        ];
    }
}
