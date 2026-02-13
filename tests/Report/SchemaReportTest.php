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

use App\Controller\AbstractController;
use App\Model\FontAwesomeImage;
use App\Model\ImageSize;
use App\Report\SchemaReport;
use App\Service\FontAwesomeImageService;
use App\Service\SchemaService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SchemaReportTest extends TestCase
{
    public function testEmpty(): void
    {
        $controller = $this->createController();
        $schemaService = $this->createSchemaService();
        $imageService = $this->createImageService();
        $report = new SchemaReport($controller, $schemaService, $imageService);
        $actual = $report->render();
        self::assertFalse($actual);
    }

    public function testWithAssociation(): void
    {
        $stringColumn = [
            'name' => 'string',
            'primary' => true,
            'unique' => true,
            'type' => 'string',
            'length' => 255,
            'required' => true,
            'foreign_table' => 'table2',
            'default' => 'fake',
        ];
        $association1 = [
            'name' => 'association1',
            'inverse' => false,
            'table' => 'table2',
        ];
        $association2 = [
            'name' => 'association2',
            'inverse' => true,
            'table' => 'table1',
        ];
        $table1 = [
            'name' => 'table1',
            'columns' => [$stringColumn],
            'indexes' => [],
            'associations' => [$association1],
            'records' => 0,
            'size' => 0,
            'sql' => '',
        ];
        $table2 = [
            'name' => 'table2',
            'columns' => [$stringColumn],
            'indexes' => [],
            'associations' => [$association2],
            'records' => 0,
            'size' => 0,
            'sql' => '',
        ];

        $controller = $this->createController();
        $schemaService = $this->createSchemaService([
            'table1' => $table1,
            'table2' => $table2,
        ]);
        $imageService = $this->createImageService();
        $imageService->expects(self::exactly(2))
            ->method('getImage')
            ->willReturnOnConsecutiveCalls(
                $this->createFontAwesomeImage(),
                null
            );
        $report = new SchemaReport($controller, $schemaService, $imageService);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testWithoutColumnAndIndex(): void
    {
        $table = [
            'name' => 'table',
            'columns' => [],
            'indexes' => [],
            'associations' => [],
            'records' => 0,
            'size' => 0,
            'sql' => '',
        ];
        $controller = $this->createController();
        $schemaService = $this->createSchemaService(['table' => $table]);

        $imageService = $this->createImageService();
        $report = new SchemaReport($controller, $schemaService, $imageService);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testWithTable(): void
    {
        $stringColumn = [
            'name' => 'string',
            'primary' => true,
            'unique' => true,
            'type' => 'string',
            'length' => 255,
            'required' => true,
            'foreign_table' => null,
            'default' => 'fake',
        ];
        $booleanColumn = [
            'name' => 'boolean',
            'primary' => true,
            'unique' => true,
            'type' => 'string',
            'length' => 255,
            'required' => false,
            'foreign_table' => null,
            'default' => 'fake',
        ];
        $index1 = [
            'name' => 'index1',
            'primary' => true,
            'unique' => true,
            'columns' => ['string', 'boolean'],
        ];
        $index2 = [
            'name' => 'index2',
            'primary' => false,
            'unique' => false,
            'columns' => ['string'],
        ];
        $table = [
            'name' => 'table',
            'columns' => [$stringColumn, $booleanColumn],
            'indexes' => [$index1, $index2],
            'associations' => [],
            'records' => 0,
            'size' => 0,
            'sql' => '',
        ];
        $controller = $this->createController();
        $schemaService = $this->createSchemaService(['table' => $table]);
        $imageService = $this->createImageService();
        $report = new SchemaReport($controller, $schemaService, $imageService);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    private function createController(): AbstractController
    {
        return self::createStub(AbstractController::class);
    }

    private function createFontAwesomeImage(): FontAwesomeImage
    {
        $path = __DIR__ . '/../files/images/example.png';
        $content = (string) \file_get_contents($path);
        $size = new ImageSize(124, 147);

        return new FontAwesomeImage($content, $size, 96);
    }

    private function createImageService(): MockObject&FontAwesomeImageService
    {
        return $this->createMock(FontAwesomeImageService::class);
    }

    private function createSchemaService(array $tables = []): MockObject&SchemaService
    {
        $schemaService = $this->createMock(SchemaService::class);
        $schemaService->method('getTables')
            ->willReturn($tables);

        return $schemaService;
    }
}
