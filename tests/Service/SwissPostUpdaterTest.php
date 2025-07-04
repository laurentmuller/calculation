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

use App\Service\ApplicationService;
use App\Service\SwissPostService;
use App\Service\SwissPostUpdater;
use App\Tests\KernelServiceTestCase;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SwissPostUpdaterTest extends KernelServiceTestCase
{
    use TranslatorMockTrait;

    private MockObject&ApplicationService $application;
    private string $databaseName;
    private SwissPostUpdater $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $source = __DIR__ . '/../files/sqlite/swiss_test_empty.sqlite';
        $this->databaseName = __DIR__ . '/../files/csv/swiss_test_model.sqlite';
        \copy($source, $this->databaseName);

        $this->application = $this->createMock(ApplicationService::class);
        $factory = $this->getService(FormFactoryInterface::class);
        $service = new SwissPostService($this->databaseName);

        $logger = $this->createMock(LoggerInterface::class);
        $translator = $this->createMockTranslator();

        $this->service = new SwissPostUpdater($this->application, $factory, $service);
        $this->service->setTranslator($translator)
            ->setLogger($logger);
    }

    #[\Override]
    protected function tearDown(): void
    {
        if (\is_file($this->databaseName)) {
            \unlink($this->databaseName);
        }
        parent::tearDown();
    }

    public function testCreateForm(): void
    {
        $actual = $this->service->createForm();
        self::assertTrue($actual->has('file'));
        self::assertTrue($actual->has('overwrite'));
    }

    public function testImport2FilesInZip(): void
    {
        $sourceFile = __DIR__ . '/../files/zip/two_files.zip';
        $actual = $this->service->import($sourceFile, false);
        self::assertFalse($actual->isValid());
    }

    public function testImportEmptyZip(): void
    {
        $sourceFile = __DIR__ . '/../files/zip/empty.zip';
        $actual = $this->service->import($sourceFile, false);
        self::assertFalse($actual->isValid());
    }

    public function testImportFileContentEmpty(): void
    {
        $sourceFile = __DIR__ . '/../files/zip/small_post_address_empty.zip';
        $actual = $this->service->import($sourceFile, false);
        self::assertFalse($actual->isValid());
    }

    public function testImportFileEmpty(): void
    {
        $sourceFile = __DIR__ . '/../files/txt/empty.txt';
        $actual = $this->service->import($sourceFile, false);
        self::assertFalse($actual->isValid());
    }

    public function testImportFileNoExist(): void
    {
        $actual = $this->service->import('fake_file', false);
        self::assertFalse($actual->isValid());
    }

    public function testImportInvalidFile(): void
    {
        $sourceFile = $this->createMock(UploadedFile::class);
        $sourceFile->method('isValid')
            ->willReturn(false);
        $actual = $this->service->import($sourceFile, false);
        self::assertFalse($actual->isValid());
    }

    public function testImportNoFile(): void
    {
        $sourceFile = '';
        $actual = $this->service->import($sourceFile, false);
        self::assertFalse($actual->isValid());
    }

    public function testImportNotArchive(): void
    {
        $sourceFile = __FILE__;
        $actual = $this->service->import($sourceFile, false);
        self::assertFalse($actual->isValid());
    }

    public function testImportSameAsDatabase(): void
    {
        $sourceFile = $this->databaseName;
        $actual = $this->service->import($sourceFile, false);
        self::assertFalse($actual->isValid());
    }

    public function testImportStateNoFound(): void
    {
        $this->databaseName = __DIR__ . '/../files/sqlite/not_exist.sqlite';
        $this->application = $this->createMock(ApplicationService::class);
        $factory = $this->getService(FormFactoryInterface::class);
        $service = new SwissPostService($this->databaseName);
        $logger = $this->createMock(LoggerInterface::class);
        $translator = $this->createMockTranslator();

        $this->service = new SwissPostUpdater($this->application, $factory, $service);
        $this->service->setTranslator($translator)
            ->setLogger($logger);

        $sourceFile = __DIR__ . '/../files/zip/small_post_address.zip';
        $actual = $this->service->import($sourceFile, false);
        self::assertFalse($actual->isValid());
    }

    public function testImportSuccess(): void
    {
        $date = new DatePoint('2024-01-17');
        $this->application->method('getLastImport')
            ->willReturn($date);
        $sourceFile = __DIR__ . '/../files/zip/small_post_address.zip';
        $actual = $this->service->import($sourceFile, false);
        self::assertTrue($actual->isValid());

        $entries = $actual->getValidEntries();
        self::assertSame(29, $entries['state']);
        self::assertSame(1, $entries['city']);
        self::assertSame(2, $entries['street']);

        $entries = $actual->getInvalidEntries();
        self::assertSame(0, $entries['state']);
        self::assertSame(0, $entries['city']);
        self::assertSame(0, $entries['street']);
    }

    public function testImportSuccessOverwrite(): void
    {
        $sourceFile = __DIR__ . '/../files/zip/small_post_address.zip';
        $actual = $this->service->import($sourceFile, true);
        self::assertTrue($actual->isValid());

        $entries = $actual->getValidEntries();
        self::assertSame(29, $entries['state']);
        self::assertSame(1, $entries['city']);
        self::assertSame(2, $entries['street']);

        $entries = $actual->getInvalidEntries();
        self::assertSame(0, $entries['state']);
        self::assertSame(0, $entries['city']);
        self::assertSame(0, $entries['street']);
    }

    public function testImportUploadedFile(): void
    {
        $path = __DIR__ . '/../files/zip/small_post_address.zip';
        $originalName = \basename($path);
        $sourceFile = new UploadedFile(
            path: $path,
            originalName: $originalName,
            test: true
        );
        $actual = $this->service->import($sourceFile, false);
        self::assertTrue($actual->isValid());
    }

    public function testImportValidityOlder(): void
    {
        $date = new DatePoint('2024-07-17');
        $this->application->method('getLastImport')
            ->willReturn($date);
        $sourceFile = __DIR__ . '/../files/zip/small_post_address.zip';
        $actual = $this->service->import($sourceFile, false);
        self::assertFalse($actual->isValid());
    }
}
