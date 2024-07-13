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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(SpreadsheetDocument::class)]
#[CoversClass(WorksheetDocument::class)]
class SpreadsheetDocumentTest extends TestCase
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
        $doc1->addSheet(new WorksheetDocument(title: 'Fake'));
        /** @psalm-var WorksheetDocument $sheet */
        $sheet = $doc1->getSheetByName('Fake');
        $clone = clone $sheet;
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

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testInitialize(): void
    {
        $expected = 'Active Title';
        $doc = new class($this->createMockTranslator()) extends SpreadsheetDocument {
            public function runInitialize(AbstractController $controller, string $title, bool $landscape = false): void
            {
                parent::initialize($controller, $title, $landscape);
            }
        };
        $controller = $this->createMock(AbstractController::class);
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
        self::assertInstanceOf(TranslatorInterface::class, $doc->getTranslator());
    }

    public function testSetActiveSheetIndexByName(): void
    {
        $doc = $this->createDocument();
        $doc->addSheet(new WorksheetDocument(title: 'Fake'));
        $actual = $doc->setActiveSheetIndexByName('Fake');
        self::assertInstanceOf(WorksheetDocument::class, $actual);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testSetActiveTitle(): void
    {
        $expected = 'Active Title';
        $controller = $this->createMock(AbstractController::class);
        $doc = $this->createDocument();
        $doc->setActiveTitle($expected, $controller);
        $actual = $doc->getActiveSheet()->getTitle();
        self::assertSame($expected, $actual);
    }

    public function testSetDescriptionTrans(): void
    {
        $doc = $this->createDocument();
        $doc->setDescriptionTrans('id');
        $actual = $doc->getProperties()->getDescription();
        self::assertSame('id', $actual);
    }

    public function testSetTitleTrans(): void
    {
        $doc = $this->createDocument();
        $doc->setTitleTrans('id');
        $actual = $doc->getTitle();
        self::assertSame('id', $actual);
    }

    private function createDocument(): SpreadsheetDocument
    {
        return new SpreadsheetDocument($this->createMockTranslator());
    }
}
