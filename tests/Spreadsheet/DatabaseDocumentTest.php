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

namespace App\Tests\Spreadsheet;

use App\Interfaces\DocumentHelperInterface;
use App\Service\DatabaseInfoService;
use App\Spreadsheet\DatabaseDocument;
use PHPUnit\Framework\TestCase;

final class DatabaseDocumentTest extends TestCase
{
    private const array CONFIGURATION = [
        'Key' => 'Value',
        'On' => 'on',
        'Off' => 'off',
    ];

    private const array DATABASE = [
        'Name' => 'FixtureDatabase',
        'Version' => '5.7.32',
    ];

    public function testRenderEmpty(): void
    {
        $document = $this->createDocument([], []);
        $actual = $document->render();
        self::assertFalse($actual);
    }

    public function testRenderNoConfiguration(): void
    {
        $document = $this->createDocument(self::DATABASE, []);
        $actual = $document->render();
        self::assertTrue($actual);
    }

    public function testRenderNoDatabase(): void
    {
        $document = $this->createDocument([], self::CONFIGURATION);
        $actual = $document->render();
        self::assertTrue($actual);
    }

    public function testRenderSuccess(): void
    {
        $document = $this->createDocument(self::DATABASE, self::CONFIGURATION);
        $actual = $document->render();
        self::assertTrue($actual);
    }

    private function createDocument(array $database, array $configuration): DatabaseDocument
    {
        $helper = self::createStub(DocumentHelperInterface::class);
        $service = self::createMock(DatabaseInfoService::class);
        $service->method('getDatabase')
            ->willReturn($database);
        $service->method('getConfiguration')
            ->willReturn($configuration);
        $service->method('isEnabledValue')
            ->willReturnCallback(static fn (string $value): bool => 'on' === $value);
        $service->method('isDisabledValue')
            ->willReturnCallback(static fn (string $value): bool => 'off' === $value);

        return new DatabaseDocument($helper, $service);
    }
}
