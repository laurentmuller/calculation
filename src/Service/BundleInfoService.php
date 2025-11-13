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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
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
 *      size: string}
 */
readonly class BundleInfoService
{
    private string $projectDir;

    public function __construct(
        private KernelInterface $kernel,
        #[Target('calculation.symfony')]
        private CacheInterface $cache,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
    ) {
        $this->projectDir = FileUtils::normalize($projectDir);
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
        $projectDir = $this->projectDir;
        $vendorDir = FileUtils::buildPath($projectDir, 'vendor');
        foreach ($this->kernel->getBundles() as $key => $bundleObject) {
            $path = $bundleObject->getPath();
            $bundles[$key] = [
                'name' => $key,
                'namespace' => $bundleObject->getNamespace(),
                'path' => $this->makePathRelative($path),
                'package' => $this->makePathRelative($path, $vendorDir),
                'size' => FileUtils::formatSize($path),
            ];
        }
        \ksort($bundles);

        return $bundles;
    }

    private function makePathRelative(string $endPath, ?string $startPath = null): string
    {
        return \rtrim(FileUtils::makePathRelative($endPath, $startPath ?? $this->projectDir), '/src');
    }
}
