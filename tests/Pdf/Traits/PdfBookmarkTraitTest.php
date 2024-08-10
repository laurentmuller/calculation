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

use App\Pdf\PdfFont;
use App\Tests\Data\PdfDocumentBookmark;
use fpdf\Enums\PdfDestination;
use fpdf\PdfException;
use PHPUnit\Framework\TestCase;

class PdfBookmarkTraitTest extends TestCase
{
    public function testBookmarkEmpty(): void
    {
        $doc = $this->createDocument();
        $doc->addPageIndex();
        $doc->output(PdfDestination::STRING);
        self::assertSame(1, $doc->getPage());
    }

    public function testBookmarks(): void
    {
        $doc = $this->createDocument();
        $doc->addBookmark('Level 0');
        $doc->addBookmark('Level 1', level: 1);
        $doc->addPageIndex();
        $doc->output(PdfDestination::STRING);
        self::assertSame(2, $doc->getPage());
    }

    public function testBookmarksWithSeparator(): void
    {
        $doc = $this->createDocument();
        $doc->addBookmark('Level 0');
        $doc->addBookmark('Level 1', level: 1);
        $doc->addPageIndex(separator: '');
        $doc->output(PdfDestination::STRING);
        self::assertSame(2, $doc->getPage());
    }

    public function testLevelInvalid(): void
    {
        self::expectException(PdfException::class);
        $doc = $this->createDocument();
        $doc->addBookmark('Invalid Level', level: 3);
    }

    /**
     * @psalm-suppress InvalidArgument
     */
    public function testLevelNegative(): void
    {
        self::expectException(PdfException::class);
        $doc = $this->createDocument();
        // @phpstan-ignore argument.type
        $doc->addBookmark('Negative Level', level: -1);
    }

    public function testLongBookmark(): void
    {
        $bookmark = \str_repeat('Bookmark', 30);
        $doc = $this->createDocument(100);
        $doc->addBookmark($bookmark);
        $doc->addPageIndex();
        $doc->output(PdfDestination::STRING);
        self::assertSame(2, $doc->getPage());
    }

    public function testLongSeparator(): void
    {
        $separator = \str_repeat('Separator', 30);
        $doc = $this->createDocument(100);
        $doc->addBookmark('Level 0');
        $doc->addPageIndex(separator: $separator);
        $doc->output(PdfDestination::STRING);
        self::assertSame(2, $doc->getPage());
    }

    private function createDocument(?float $rightMargin = null): PdfDocumentBookmark
    {
        $doc = new PdfDocumentBookmark();
        $doc->applyFont(PdfFont::default());
        if (null !== $rightMargin) {
            $doc->setRightMargin(100);
        }
        $doc->addPage();

        return $doc;
    }
}
