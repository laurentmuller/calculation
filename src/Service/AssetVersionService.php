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

use App\Entity\User;
use App\Interfaces\DisableListenerInterface;
use App\Traits\DisableListenerTrait;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Apply the following strategy for assets:
 * <ul>
 * <li>In production mode, use the modification time of the deployment file ('.htdeployment').</li>
 * <li>In debug and test mode, use the modification time of the composer lock file ('composer.lock').</li>
 * <li>For user images folder ('images/users'), use the modification time of the image.</li>
 * </ul>
 */
#[AsEntityListener(event: Events::postUpdate, method: 'deleteCache', lazy: true, entity: User::class)]
#[AsEntityListener(event: Events::postPersist, method: 'deleteCache', lazy: true, entity: User::class)]
class AssetVersionService extends StaticVersionStrategy implements DisableListenerInterface
{
    use DisableListenerTrait;

    private const IMAGES_PATH = 'images/users/';
    private const KEY_IMAGES = 'key_asset_images';

    private readonly string $imagesPath;
    private readonly string $imagesVersion;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
        EnvironmentService $service,
        #[Target('calculation.service.asset')]
        private readonly CacheInterface $cache,
    ) {
        $version = $this->cache->get(
            'key_asset_version',
            fn (): string => $this->getBaseVersion($projectDir, $service)
        );
        $this->imagesPath = $this->canonicalize($projectDir, 'public', self::IMAGES_PATH);
        $this->imagesVersion = $this->getFileTime($this->imagesPath, $version);

        parent::__construct($version);
    }

    /**
     * @psalm-api
     *
     * @throws InvalidArgumentException
     */
    public function deleteCache(): bool
    {
        return $this->cache->delete(self::KEY_IMAGES);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getVersion(string $path): string
    {
        if (\str_starts_with($path, self::IMAGES_PATH)) {
            return $this->getUserImages()[\basename($path)] ?? $this->imagesVersion;
        }

        return parent::getVersion($path);
    }

    private function canonicalize(string ...$paths): string
    {
        return Path::canonicalize(Path::join(...$paths));
    }

    private function getBaseVersion(string $projectDir, EnvironmentService $service): string
    {
        $file = $service->isProduction() ? '.htdeployment' : 'composer.lock';

        return $this->getFileTime($this->canonicalize($projectDir, $file), Kernel::VERSION);
    }

    private function getFileTime(string $path, string $default): string
    {
        return \file_exists($path) ? (string) \filemtime($path) : $default;
    }

    /**
     * @return array<string, string>
     *
     * @throws InvalidArgumentException
     */
    private function getUserImages(): array
    {
        return $this->cache->get(self::KEY_IMAGES, function (): array {
            $images = [];
            $finder = Finder::create()
                ->in($this->imagesPath)
                ->files();
            foreach ($finder as $file) {
                $images[$file->getFilename()] = $this->getFileTime($file->getRealPath(), $this->imagesVersion);
            }

            return $images;
        });
    }
}
