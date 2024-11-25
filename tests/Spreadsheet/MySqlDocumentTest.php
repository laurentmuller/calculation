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
use App\Spreadsheet\MySqlDocument;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class MySqlDocumentTest extends TestCase
{
    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
    public function testRenderEmpty(): void
    {
        $document = $this->createDocument([], []);
        $actual = $document->render();
        self::assertFalse($actual);
    }

    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
    private function createDocument(array $database, array $configuration): MySqlDocument
    {
        $controller = $this->createMock(AbstractController::class);
        $service = $this->createMock(DatabaseInfoService::class);
        $service->method('getDatabase')
            ->willReturn($database);
        $service->method('getConfiguration')
            ->willReturn($configuration);

        return new MySqlDocument($controller, $service);
    }
}
