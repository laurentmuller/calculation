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
use App\Service\OpenWeatherService;
use App\Tests\TranslatorMockTrait;
use App\Utils\FileUtils;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[CoversClass(OpenWeatherCityUpdater::class)]
class OpenWeatherCityUpdaterTest extends TestCase
{
    use TranslatorMockTrait;

    /**
     * @throws Exception
     */
    public function testCreateForm(): void
    {
        $service = $this->createService();
        $actual = $service->createForm();
        self::assertFalse($actual->has('file'));
    }

    /**
     * @throws Exception
     */
    public function testImportFileEmpty(): void
    {
        $targetFile = $this->copy('empty.txt');

        try {
            $file = new UploadedFile($targetFile, \basename($targetFile), test: true);
            $service = $this->createService();
            $actual = $service->import($file);
            self::assertInvalid($actual, 'swisspost.error.open_archive');
        } finally {
            FileUtils::remove($targetFile);
        }
    }

    /**
     * @throws Exception
     */
    public function testImportFileEmptyGz(): void
    {
        $targetFile = $this->copy('city.list.empty.json.gz');

        try {
            $file = new UploadedFile($targetFile, \basename($targetFile), test: true);
            $service = $this->createService();
            $actual = $service->import($file);
            self::assertInvalid($actual, 'openweather.error.empty_city');
        } finally {
            FileUtils::remove($this->getDatabaseName());
            FileUtils::remove($targetFile);
        }
    }

    /**
     * @throws Exception
     */
    public function testImportFileIsInvalid(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('isValid')
            ->willReturn(false);
        $service = $this->createService();
        $actual = $service->import($file);
        self::assertInvalid($actual, 'swisspost.error.open_archive');
    }

    /**
     * @throws Exception
     */
    public function testImportFileNotGz(): void
    {
        $targetFile = $this->copy('city.list.json');

        try {
            $file = new UploadedFile($targetFile, \basename($targetFile), test: true);
            $service = $this->createService();
            $actual = $service->import($file);
            self::assertInvalid($actual, 'swisspost.error.open_archive');
        } finally {
            FileUtils::remove($targetFile);
        }
    }

    /**
     * @throws Exception
     */
    public function testImportFileValid(): void
    {
        $targetFile = $this->copy('city.list.json.gz');

        try {
            $file = new UploadedFile($targetFile, \basename($targetFile), test: true);

            $service = $this->createService();
            $actual = $service->import($file);
            self::assertArrayHasKey('result', $actual);
            self::assertArrayHasKey('valid', $actual);
            self::assertArrayHasKey('error', $actual);
            self::assertArrayHasKey('message', $actual);
            self::assertTrue($actual['result']);
            self::assertSame(10, $actual['valid']);
            self::assertSame(0, $actual['error']);
            self::assertSame('openweather.result.success', $actual['message']);
        } finally {
            FileUtils::remove($this->getDatabaseName());
            FileUtils::remove($targetFile);
        }
    }

    protected static function assertInvalid(array $actual, string $message): void
    {
        self::assertArrayHasKey('result', $actual);
        self::assertArrayHasKey('valid', $actual);
        self::assertArrayHasKey('error', $actual);
        self::assertArrayHasKey('message', $actual);
        self::assertFalse($actual['result']);
        self::assertSame(0, $actual['valid']);
        self::assertSame(0, $actual['error']);
        self::assertSame($message, $actual['message']);
    }

    private function copy(string $fileName): string
    {
        $originFile = (string) \realpath(__DIR__ . '/../Data/' . $fileName);
        $targetFile = __DIR__ . '/' . \basename($originFile);
        FileUtils::copy($originFile, $targetFile);

        return $targetFile;
    }

    /**
     * @throws Exception
     */
    private function createService(): OpenWeatherCityUpdater
    {
        $service = $this->createMock(OpenWeatherService::class);
        $service->method('getDatabaseName')
            ->willReturn($this->getDatabaseName());

        $factory = $this->createMock(FormFactoryInterface::class);
        $translator = $this->createMockTranslator();

        return new OpenWeatherCityUpdater($service, $factory, $translator);
    }

    private function getDatabaseName(): string
    {
        return __DIR__ . '/database.sqlite';
    }
}
