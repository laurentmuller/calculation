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

use App\Controller\AbstractController;
use App\Service\DatabaseInfoService;
use App\Spreadsheet\DatabaseDocument;
use PHPUnit\Framework\TestCase;

class DatabaseDocumentTest extends TestCase
{
    public function testRender(): void
    {
        $database = [
            'Name' => 'Database',
            'Version' => '5.7.32',
        ];
        $configuration = [
            'Key' => 'Value',
            'Off' => 'off',
        ];
        $document = $this->createDocument($database, $configuration);
        $actual = $document->render();
        self::assertTrue($actual);
    }

    public function testRenderEmpty(): void
    {
        $document = $this->createDocument([], []);
        $actual = $document->render();
        self::assertFalse($actual);
    }

    public function testRenderNoDatabase(): void
    {
        $configuration = [
            'Key' => 'Value',
            'Off' => 'off',
        ];
        $document = $this->createDocument([], $configuration);
        $actual = $document->render();
        self::assertTrue($actual);
    }

    private function createDocument(array $database, array $configuration): DatabaseDocument
    {
        $controller = $this->createMock(AbstractController::class);
        $service = $this->createMock(DatabaseInfoService::class);
        $service->method('getDatabase')
            ->willReturn($database);
        $service->method('getConfiguration')
            ->willReturn($configuration);

        return new DatabaseDocument($controller, $service);
    }
}
