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

namespace App\Tests\Pdf;

use App\Pdf\Events\PdfGroupEvent;
use App\Pdf\Interfaces\PdfGroupListenerInterface;
use App\Pdf\PdfColumn;
use App\Pdf\PdfDocument;
use App\Pdf\PdfGroup;
use App\Pdf\PdfGroupTable;
use App\Pdf\PdfStyle;
use fpdf\PdfTextAlignment;
use PHPUnit\Framework\TestCase;

class PdfGroupTableTest extends TestCase
{
    public function testCheckNewPage(): void
    {
        $table = $this->createTable();
        self::assertFalse($table->checkNewPage(0.0));
        self::assertTrue($table->checkNewPage(12_000.0));

        $table->setGroupBeforeHeader(true);
        self::assertFalse($table->checkNewPage(0.0));
        self::assertTrue($table->checkNewPage(12_000.0));
    }

    public function testGetGroup(): void
    {
        $table = $this->createTable();
        self::assertNotNull($table->getGroup());
        self::assertNotNull($table->getGroupStyle());
        self::assertNotNull($table->getGroup()->getStyle());
        self::assertNull($table->getGroupListener());
    }

    public function testGroupListener(): void
    {
        $table = $this->createTable();
        $table->getParent()->addPage();
        $listener = new class() implements PdfGroupListenerInterface {
            public function outputGroup(PdfGroupEvent $event): bool
            {
                TestCase::assertNotNull($event->getDocument());
                TestCase::assertNotNull($event->getGroupKey());

                return false;
            }
        };
        $table->setGroupListener($listener);
        $table->setGroupKey('key');
        self::assertNotNull($table->getParent());
    }

    public function testGroupStyle(): void
    {
        $table = $this->createTable();
        $style = PdfStyle::getBlackHeaderStyle();
        $table->setGroupStyle($style);
        self::assertSame($style, $table->getGroupStyle());
    }

    public function testOutputGroup(): void
    {
        $table = $this->createTable();
        $table->getParent()->addPage();
        $table->setGroupKey('key', false);
        $table->outputGroup();
        self::assertNotNull($table->getParent());
    }

    public function testSetGroup(): void
    {
        $table = $this->createTable();
        $table->getParent()->addPage();

        $group = $table->getGroup();
        $group->setAlignment(PdfTextAlignment::CENTER);
        $table->setGroup($group);
        self::assertSame($group, $table->getGroup());

        $group = new PdfGroup('key');
        $group->apply($table->getParent());
        $table->setGroup($group);
        self::assertSame($group, $table->getGroup());
    }

    private function createTable(): PdfGroupTable
    {
        $document = new PdfDocument();

        return PdfGroupTable::instance($document)
            ->addColumn(new PdfColumn(''));
    }
}
