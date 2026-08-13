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

namespace App\Tests\Report;

use App\Interfaces\DocumentHelperInterface;
use App\Report\DatabaseReport;
use App\Service\DatabaseInfoService;
use App\Service\FontAwesomeCellService;
use PHPUnit\Framework\TestCase;

final class DatabaseReportTest extends TestCase
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
        $document = $this->createReport([], []);
        $actual = $document->render();
        self::assertFalse($actual);
    }

    public function testRenderNoConfiguration(): void
    {
        $document = $this->createReport(self::DATABASE, []);
        $actual = $document->render();
        self::assertTrue($actual);
    }

    public function testRenderNoDatabase(): void
    {
        $document = $this->createReport([], self::CONFIGURATION);
        $actual = $document->render();
        self::assertTrue($actual);
    }

    public function testRenderSuccess(): void
    {
        $document = $this->createReport(self::DATABASE, self::CONFIGURATION);
        $actual = $document->render();
        self::assertTrue($actual);
    }

    private function createReport(array $database, array $configuration): DatabaseReport
    {
        $helper = self::createStub(DocumentHelperInterface::class);
        $cellService = self::createStub(FontAwesomeCellService::class);
        $databaseService = self::createStub(DatabaseInfoService::class);
        $databaseService->method('getDatabase')
            ->willReturn($database);
        $databaseService->method('getConfiguration')
            ->willReturn($configuration);
        $databaseService->method('isEnabledValue')
            ->willReturnCallback(static fn (string $value): bool => 'on' === $value);
        $databaseService->method('isDisabledValue')
            ->willReturnCallback(static fn (string $value): bool => 'off' === $value);

        return new DatabaseReport($helper, $databaseService, $cellService);
    }
}
