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

namespace App\Tests\Service;

use App\Enums\Environment;
use App\Service\AssetVersionService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Path;

#[\PHPUnit\Framework\Attributes\CoversClass(AssetVersionService::class)]
class AssetVersionServiceTest extends TestCase
{
    private string $defaultVersion;
    private string $imagesVersion;
    private AssetVersionService $service;

    protected function setUp(): void
    {
        $projectDir = Path::canonicalize(__DIR__ . '/../Data');
        $imagesDir = Path::canonicalize($projectDir . '/public/images/users');
        if (!\file_exists($imagesDir)) {
            \mkdir($imagesDir, recursive: true);
        }
        $this->defaultVersion = (string) \filemtime($projectDir . '/composer.lock');
        $this->imagesVersion = (string) \filemtime($imagesDir);
        $this->service = new AssetVersionService($projectDir, Environment::TEST->value);
    }

    public static function getPaths(): array
    {
        return [
            [''],
            ['/'],
            ['/fake'],
            ['/images'],
            ['/images/'],
            ['images/users/', true],
            ['images/users/fake.png', true],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getPaths')]
    public function testApplyVersion(string $path, bool $isImage = false): void
    {
        $version = $this->getVersion($isImage);
        $expected = \sprintf('%s?%s', $path, $version);
        $actual = $this->service->applyVersion($path);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getPaths')]
    public function testPath(string $path, bool $isImage = false): void
    {
        $expected = $this->getVersion($isImage);
        $actual = $this->service->getVersion($path);
        self::assertSame($expected, $actual);
    }

    private function getVersion(bool $isImage): string
    {
        return $isImage ? $this->imagesVersion : $this->defaultVersion;
    }
}
