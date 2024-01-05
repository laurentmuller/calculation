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

use App\Enums\Environment;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;

/**
 * Apply the following strategy for assets:
 * <ul>
 * <li>In production mode, use the modification time of the deployment file ('.htdeployment').</li>
 * <li>In debug and test mode, use the modification time of the composer lock file ('composer.lock').</li>
 * <li>Use the modification time of the directory for the user images folder ('images/users').</li>
 * </ul>
 */
class AssetVersionStrategy extends StaticVersionStrategy
{
    private const IMAGES_PATH = 'images/users/';

    private readonly string $imagesVersion;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
        #[Autowire('%kernel.environment%')]
        string $env
    ) {
        $production = Environment::from($env)->isProduction();
        $file = $production ? '.htdeployment' : 'composer.lock';
        parent::__construct($this->time($projectDir, $file));
        $this->imagesVersion = $this->time($projectDir, 'public', self::IMAGES_PATH);
    }

    public function getVersion(string $path): string
    {
        if (\str_starts_with($path, self::IMAGES_PATH)) {
            return $this->imagesVersion;
        }

        return parent::getVersion($path);
    }

    private function time(string ...$paths): string
    {
        return (string) \filemtime(Path::join(...$paths));
    }
}
