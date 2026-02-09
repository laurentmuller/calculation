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
use App\Spreadsheet\SpreadsheetDocument;
use App\Spreadsheet\WorksheetDocument;
use App\Tests\TranslatorMockTrait;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPUnit\Framework\TestCase;

final class SpreadsheetDocumentTest extends TestCase
{
    use TranslatorMockTrait;

    public function testAddExternalSheetException(): void
    {
        self::expectException(Exception::class);
        $doc = $this->createDocument();
        $doc->addExternalSheet(new Worksheet());
    }

    public function testAddExternalSheetSuccess(): void
    {
        $doc1 = $this->createDocument();
        $sheet = $this->createWorksheet();
        $doc1->addSheet($sheet);
        $clone = clone $sheet;
        $clone->setTitle('Fake Clone');
        $doc1->addSheet($clone);

        $doc2 = $this->createDocument();
        $actual = $doc2->addExternalSheet($clone);
        self::assertSame($clone, $actual);
    }

    public function testAddSheetException(): void
    {
        self::expectException(Exception::class);
        $doc = $this->createDocument();
        $doc->addSheet(new Worksheet());
    }

    public function testAddSheetSuccess(): void
    {
        $doc = $this->createDocument();
        $sheet = $this->createWorksheet();
        $actual = $doc->addSheet($sheet);
        self::assertSame($sheet, $actual);
    }

    public function testCreateSheetAndTitle(): void
    {
        $controller = self::createStub(AbstractController::class);
        $doc = $this->createDocument();
        $actual = $doc->createSheetAndTitle($controller, 'My Title');
        self::assertSame('My Title', $actual->getTitle());
        self::assertSame($actual, $doc->getActiveSheet());
    }

    public function testGetAllSheets(): void
    {
        $doc = $this->createDocument();
        $actual = $doc->getAllSheets();
        self::assertContainsOnlyInstancesOf(WorksheetDocument::class, $actual);
    }

    public function testGetSheetByNameOrThrow(): void
    {
        self::expectException(Exception::class);
        $doc = $this->createDocument();
        $doc->getSheetByNameOrThrow('Fake');
    }

    public function testInitialize(): void
    {
        $expected = 'Active Title';
        $doc = new class($this->createMockTranslator()) extends SpreadsheetDocument {
            public function runInitialize(AbstractController $controller, string $title, bool $landscape = false): void
            {
                parent::initialize($controller, $title, $landscape);
            }
        };
        $controller = self::createStub(AbstractController::class);
        $doc->runInitialize($controller, $expected, true);
        self::assertSame($expected, $doc->getTitle());
    }

    public function testProperties(): void
    {
        $doc = $this->createDocument();
        self::assertNull($doc->getTitle());

        $doc->setCategory('category');
        $doc->setCompany('company');
        $doc->setDescription('description');
        $doc->setSubject('subject');
        $doc->setTitle('title');
        $doc->setUserName('user_name');
        self::assertSame('title', $doc->getTitle());
    }

    public function testSetActiveSheetIndexByName(): void
    {
        $doc = $this->createDocument();
        $doc->addSheet($this->createWorksheet());
        $actual = $doc->setActiveSheetIndexByName('Fake');
        self::assertSame('Fake', $actual->getTitle());
    }

    public function testSetActiveTitle(): void
    {
        $expected = 'Active Title';
        $controller = self::createStub(AbstractController::class);
        $doc = $this->createDocument();
        $doc->setActiveTitle($expected, $controller);
        $actual = $doc->getActiveSheet()->getTitle();
        self::assertSame($expected, $actual);
    }

    public function testSetTranslatedDescription(): void
    {
        $expected = 'Translated description';
        $doc = $this->createDocument();
        $doc->setTranslatedDescription($expected);
        $actual = $doc->getProperties()->getDescription();
        self::assertSame($expected, $actual);
    }

    public function testSetTranslatedTitle(): void
    {
        $expected = 'Translated title';
        $doc = $this->createDocument();
        $doc->setTranslatedTitle($expected);
        $actual = $doc->getTitle();
        self::assertSame($expected, $actual);
    }

    private function createDocument(): SpreadsheetDocument
    {
        return new SpreadsheetDocument($this->createMockTranslator());
    }

    private function createWorksheet(string $title = 'Fake'): WorksheetDocument
    {
        return new WorksheetDocument(title: $title);
    }
}
