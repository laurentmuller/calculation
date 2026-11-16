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
use App\Entity\User;
use App\Enums\Environment;
use App\Interfaces\DisableListenerInterface;
use App\Traits\DisableListenerTrait;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Apply the following strategy for assets:
 * <ul>
 * <li>In production mode, use the modification time of the deployment file ('.htdeployment').</li>
 * <li>In debug or test mode, use the modification time of the composer lock file ('composer.lock').</li>
 * <li>For user images, use the modification time of the 'images/users' folder.</li>
 * </ul>
 */
#[AsEntityListener(event: Events::postPersist, method: 'deleteCache', lazy: true, entity: User::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'deleteCache', lazy: true, entity: User::class)]
#[AsEntityListener(event: Events::postRemove, method: 'deleteCache', lazy: true, entity: User::class)]
class AssetVersionService extends StaticVersionStrategy implements DisableListenerInterface
{
    use DisableListenerTrait;

    private const string IMAGES_PATH = 'images/users/';

    private readonly string $imagesVersion;

    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        string $projectDir,
        #[Autowire(param: 'app_env')]
        Environment $environment,
        #[Target(CacheAttributes::CACHE_ASSET)]
        private readonly CacheInterface $cache,
    ) {
        $baseVersion = $this->cache->get(
            'asset_base_version',
            fn (): string => $this->getBaseVersion($projectDir, $environment)
        );
        $this->imagesVersion = $this->cache->get(
            'asset_images_version',
            fn (): string => $this->getImagesVersion($projectDir, $baseVersion)
        );
        parent::__construct($baseVersion);
    }

    public function deleteCache(): bool
    {
        return $this->cache->delete('asset_images_version');
    }

    #[\Override]
    public function getVersion(string $path): string
    {
        if (\str_starts_with($path, self::IMAGES_PATH)) {
            return $this->imagesVersion;
        }

        return parent::getVersion($path);
    }

    private function getBaseVersion(string $projectDir, Environment $environment): string
    {
        $file = $environment->isProduction() ? '.htdeployment' : 'composer.lock';

        return $this->getFileTime(Path::join($projectDir, $file));
    }

    private function getFileTime(string $path, string $default = Kernel::VERSION): string
    {
        return \file_exists($path) ? (string) \filemtime($path) : $default;
    }

    private function getImagesVersion(string $projectDir, string $baseVersion): string
    {
        $path = Path::join($projectDir, 'public', self::IMAGES_PATH);

        return $this->getFileTime($path, $baseVersion);
    }
}
