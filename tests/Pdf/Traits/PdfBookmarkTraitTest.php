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

namespace App\Tests\Pdf\Traits;

use App\Pdf\PdfDocument;
use App\Pdf\PdfFont;
use App\Pdf\Traits\PdfBookmarkTrait;
use fpdf\PdfDestination;
use fpdf\PdfException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfBookmarkTrait::class)]
class PdfBookmarkTraitTest extends TestCase
{
    public function testBookmarkEmpty(): void
    {
        $doc = new PdfDocument();
        $doc->applyFont(PdfFont::default());
        $doc->addPage();
        $doc->addPageIndex();
        $doc->output(PdfDestination::STRING);
        self::assertSame(1, $doc->getPage());
    }

    public function testBookmarks(): void
    {
        $doc = new PdfDocument();
        $doc->applyFont(PdfFont::default());
        $doc->addPage();
        $doc->addBookmark('Level 0');
        $doc->addBookmark('Level 1', level: 1);
        $doc->addPageIndex();
        $doc->output(PdfDestination::STRING);
        self::assertSame(2, $doc->getPage());
    }

    public function testBookmarksWithSeparator(): void
    {
        $doc = new PdfDocument();
        $doc->applyFont(PdfFont::default());
        $doc->addPage();
        $doc->addBookmark('Level 0');
        $doc->addBookmark('Level 1', level: 1);
        $doc->addPageIndex(separator: '');
        $doc->output(PdfDestination::STRING);
        self::assertSame(2, $doc->getPage());
    }

    public function testInvalidLevel(): void
    {
        self::expectException(PdfException::class);
        $doc = new PdfDocument();
        $doc->applyFont(PdfFont::default());
        $doc->addBookmark('Invalid Level', level: 3);
    }

    public function testLongBookmark(): void
    {
        $bookmark = \str_repeat('Bookmark', 30);
        $doc = new PdfDocument();
        $doc->applyFont(PdfFont::default());
        $doc->setRightMargin(100);
        $doc->addPage();
        $doc->addBookmark($bookmark);
        $doc->addPageIndex();
        $doc->output(PdfDestination::STRING);
        self::assertSame(2, $doc->getPage());
    }

    public function testLongSeparator(): void
    {
        $separator = \str_repeat('Separator', 30);
        $doc = new PdfDocument();
        $doc->applyFont(PdfFont::default());
        $doc->setRightMargin(100);
        $doc->addPage();
        $doc->addBookmark('Level 0');
        $doc->addPageIndex(separator: $separator);
        $doc->output(PdfDestination::STRING);
        self::assertSame(2, $doc->getPage());
    }
}
