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

use App\Service\OpenWeatherCityUpdater;
use App\Tests\TranslatorMockTrait;
use App\Utils\FileUtils;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class OpenWeatherCityUpdaterTest extends TestCase
{
    use TranslatorMockTrait;

    private string $tempPath;

    #[\Override]
    protected function setUp(): void
    {
        $this->tempPath = (string) FileUtils::tempDir();
    }

    #[\Override]
    protected function tearDown(): void
    {
        FileUtils::remove($this->tempPath);
    }

    public function testCreateForm(): void
    {
        $service = $this->createService();
        $actual = $service->createForm();
        self::assertFalse($actual->has('file'));
    }

    public function testImportFileEmpty(): void
    {
        $targetFile = $this->copy('txt/empty.txt');

        try {
            $file = new UploadedFile($targetFile, \basename($targetFile), test: true);
            $service = $this->createService();
            $actual = $service->import($file);
            $this->assertHasKeys($actual);
            $this->assertInvalid($actual, 'swisspost.error.open_archive');
        } finally {
            FileUtils::remove($targetFile);
        }
    }

    public function testImportFileEmptyGz(): void
    {
        $targetFile = $this->copy('city/list.empty.json.gz');

        try {
            $file = new UploadedFile($targetFile, \basename($targetFile), test: true);
            $service = $this->createService();
            $actual = $service->import($file);
            $this->assertHasKeys($actual);
            $this->assertInvalid($actual, 'openweather.error.empty_city');
        } finally {
            FileUtils::remove($this->getDatabaseName());
            FileUtils::remove($targetFile);
        }
    }

    public function testImportFileInvalidJson(): void
    {
        $targetFile = $this->copy('city/list.invalid.json.gz');

        try {
            $file = new UploadedFile($targetFile, \basename($targetFile), test: true);
            $service = $this->createService();
            $actual = $service->import($file);
            $this->assertHasKeys($actual);
            $this->assertInvalid($actual, 'swisspost.error.open_archive');
        } finally {
            FileUtils::remove($targetFile);
        }
    }

    public function testImportFileIsInvalid(): void
    {
        $originalName = 'test.txt';
        $path = FileUtils::buildPath($this->tempPath, $originalName);
        $file = new UploadedFile($path, $originalName, error: \UPLOAD_ERR_NO_FILE);
        $service = $this->createService();
        $actual = $service->import($file);
        $this->assertHasKeys($actual);
        $this->assertInvalid($actual, 'swisspost.error.open_archive');
    }

    public function testImportFileNotGz(): void
    {
        $targetFile = $this->copy('city/list.json');

        try {
            $file = new UploadedFile($targetFile, \basename($targetFile), test: true);
            $service = $this->createService();
            $actual = $service->import($file);
            $this->assertHasKeys($actual);
            $this->assertInvalid($actual, 'swisspost.error.open_archive');
        } finally {
            FileUtils::remove($targetFile);
        }
    }

    public function testImportFileValid(): void
    {
        $targetFile = $this->copy('city/list.json.gz');

        try {
            $file = new UploadedFile($targetFile, \basename($targetFile), test: true);
            $service = $this->createService();
            $actual = $service->import($file);
            $this->assertHasKeys($actual);
            self::assertTrue($actual['result']);
            self::assertSame(10, $actual['valid']);
            self::assertSame(0, $actual['error']);
            self::assertSame('openweather.result.success', $actual['message']);
        } finally {
            FileUtils::remove($this->getDatabaseName());
            FileUtils::remove($targetFile);
        }
    }

    private function assertHasKeys(array $actual): void
    {
        self::assertArrayHasKey('result', $actual);
        self::assertArrayHasKey('valid', $actual);
        self::assertArrayHasKey('error', $actual);
        self::assertArrayHasKey('message', $actual);
    }

    private function assertInvalid(array $actual, string $message): void
    {
        self::assertFalse($actual['result']);
        self::assertSame(0, $actual['valid']);
        self::assertSame(0, $actual['error']);
        self::assertSame($message, $actual['message']);
    }

    private function copy(string $fileName): string
    {
        $path = FileUtils::realPath(__DIR__ . '/../files');
        $originFile = FileUtils::buildPath($path, $fileName);
        $targetFile = FileUtils::buildPath($this->tempPath, \basename($originFile));
        FileUtils::copy($originFile, $targetFile);

        return $targetFile;
    }

    private function createService(): OpenWeatherCityUpdater
    {
        $databaseName = $this->getDatabaseName();
        $factory = $this->createMock(FormFactoryInterface::class);
        $translator = $this->createMockTranslator();

        return new OpenWeatherCityUpdater($databaseName, $factory, $translator);
    }

    private function getDatabaseName(): string
    {
        return FileUtils::buildPath($this->tempPath, 'database.sqlite');
    }
}
