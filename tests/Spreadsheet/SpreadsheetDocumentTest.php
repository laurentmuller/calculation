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
use App\Spreadsheet\HeaderFooter;
use App\Spreadsheet\SpreadsheetDocument;
use App\Spreadsheet\WorksheetDocument;
use App\Tests\TranslatorMockTrait;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(SpreadsheetDocument::class)]
#[CoversClass(WorksheetDocument::class)]
#[CoversClass(HeaderFooter::class)]
class SpreadsheetDocumentTest extends TestCase
{
    use TranslatorMockTrait;

    public function testAddExternalSheetException(): void
    {
        self::expectException(Exception::class);
        $doc = new SpreadsheetDocument($this->createMockTranslator());
        $doc->addExternalSheet(new Worksheet());
    }

    public function testAddExternalSheetSuccess(): void
    {
        $doc1 = new SpreadsheetDocument($this->createMockTranslator());
        $doc1->addSheet(new WorksheetDocument(title: 'Fake'));
        /** @psalm-var WorksheetDocument $sheet */
        $sheet = $doc1->getSheetByName('Fake');
        $clone = clone $sheet;
        $doc2 = new SpreadsheetDocument($this->createMockTranslator());
        $actual = $doc2->addExternalSheet($clone);
        self::assertSame($clone, $actual);
    }

    public function testAddSheetException(): void
    {
        self::expectException(Exception::class);
        $doc = new SpreadsheetDocument($this->createMockTranslator());
        $doc->addSheet(new Worksheet());
    }

    public function testAddSheetSuccess(): void
    {
        $doc = new SpreadsheetDocument($this->createMockTranslator());
        $sheet = new WorksheetDocument(title: 'Fake');
        $actual = $doc->addSheet($sheet);
        self::assertSame($sheet, $actual);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testCreateSheetAndTitle(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $doc = new SpreadsheetDocument($this->createMockTranslator());
        $actual = $doc->createSheetAndTitle($controller, 'My Title');
        self::assertSame('My Title', $actual->getTitle());
        self::assertSame($actual, $doc->getActiveSheet());
    }

    public function testGetAllSheets(): void
    {
        $doc = new SpreadsheetDocument($this->createMockTranslator());
        $actual = $doc->getAllSheets();
        self::assertContainsOnlyInstancesOf(WorksheetDocument::class, $actual);
    }

    public function testGetSheetByNameOrThrow(): void
    {
        self::expectException(Exception::class);
        $doc = new SpreadsheetDocument($this->createMockTranslator());
        $doc->getSheetByNameOrThrow('Fake');
    }

    public function testProperties(): void
    {
        $doc = new SpreadsheetDocument($this->createMockTranslator());
        self::assertNull($doc->getTitle());

        $doc->setCategory('category');
        $doc->setCompany('company');
        $doc->setDescription('description');
        $doc->setSubject('subject');
        $doc->setTitle('title');
        $doc->setUserName('user_name');

        self::assertSame('title', $doc->getTitle());
        self::assertInstanceOf(TranslatorInterface::class, $doc->getTranslator());
    }
}
