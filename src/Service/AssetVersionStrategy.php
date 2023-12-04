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
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Use the modification time of the composer lock file (composer.lock) for the version.
 *
 * For the user images folder, the file modification time is used.
 */
class AssetVersionStrategy extends StaticVersionStrategy
{
    private const USER_IMAGES = 'images/users/';

    private readonly string $publicDir;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        parent::__construct((string) \filemtime($projectDir . '/composer.lock'));
        $this->publicDir = FileUtils::normalize($projectDir . '/public');
    }

    public function getVersion(string $path): string
    {
        $default = parent::getVersion($path);
        if (\str_starts_with($path, self::USER_IMAGES)) {
            return $this->getImageVersion($path, $default);
        }

        return $default;
    }

    private function getImageVersion(string $path, string $default): string
    {
        $file = FileUtils::buildPath($this->publicDir, $path);
        if (!FileUtils::exists($file)) {
            return $default;
        }
        $version = \filemtime($file);
        if (!\is_int($version)) {
            return $default;
        }

        return (string) $version;
    }
}
