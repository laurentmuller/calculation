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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Filesystem\Path;

final class AssetVersionServiceTest extends TestCase
{
    private string $defaultVersion;
    private string $imagesVersion;
    private AssetVersionService $service;

    #[\Override]
    protected function setUp(): void
    {
        $projectDir = Path::canonicalize(__DIR__ . '/../files/lock');
        $imagesDir = Path::canonicalize($projectDir . '/public/images/users');
        if (!\file_exists($imagesDir)) {
            \mkdir($imagesDir, recursive: true);
        }
        $this->imagesVersion = (string) \filemtime($imagesDir);
        $this->defaultVersion = (string) \filemtime($projectDir . '/composer.lock');
        $this->service = new AssetVersionService($projectDir, Environment::TEST, new ArrayAdapter());
    }

    public static function getPaths(): \Generator
    {
        yield [''];
        yield ['/'];
        yield ['/fake'];
        yield ['images'];
        yield ['/images'];
        yield ['/images/'];
        yield ['images/users/', true];
        yield ['images/users/fake.png', true];
    }

    #[DataProvider('getPaths')]
    public function testApplyVersion(string $path, bool $useImageVersion = false): void
    {
        $version = $this->getVersion($useImageVersion);
        $expected = \sprintf('%s?%s', $path, $version);
        $actual = $this->service->applyVersion($path);
        self::assertSame($expected, $actual);
    }

    public function testDeleteCache(): void
    {
        $actual = $this->service->deleteCache();
        self::assertTrue($actual);
    }

    #[DataProvider('getPaths')]
    public function testPath(string $path, bool $useImageVersion = false): void
    {
        $expected = $this->getVersion($useImageVersion);
        $actual = $this->service->getVersion($path);
        self::assertSame($expected, $actual);
    }

    private function getVersion(bool $useImageVersion): string
    {
        return $useImageVersion ? $this->imagesVersion : $this->defaultVersion;
    }
}
