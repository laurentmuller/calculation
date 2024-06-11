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

namespace App\Tests\Pdf\Html;

use App\Pdf\Html\HtmlBrChunk;
use App\Pdf\Html\HtmlLiChunk;
use App\Pdf\Html\HtmlOlChunk;
use App\Pdf\Html\HtmlPageBreakChunk;
use App\Pdf\Html\HtmlParentChunk;
use App\Pdf\Html\HtmlParser;
use App\Pdf\Html\HtmlTextChunk;
use App\Pdf\Html\HtmlUlChunk;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlParser::class)]
class HtmlParserTest extends TestCase
{
    public function testBrChunk(): void
    {
        $parser = new HtmlParser('<body><br></body>');
        $actual = $parser->parse();
        self::assertInstanceOf(HtmlParentChunk::class, $actual);
        self::assertNotEmpty($actual->getChildren());
        self::assertCount(1, $actual);
        $actual = $actual->getChildren()[0];
        self::assertInstanceOf(HtmlBrChunk::class, $actual);
    }

    public function testEmpty(): void
    {
        $parser = new HtmlParser('');
        $actual = $parser->parse();
        self::assertNull($actual);
    }

    public function testEmptyBody(): void
    {
        $parser = new HtmlParser('<html><body></body></html>');
        $actual = $parser->parse();
        self::assertNull($actual);
    }

    public function testLiChunk(): void
    {
        $parser = new HtmlParser('<body><li></li></body>');
        $actual = $parser->parse();
        self::assertInstanceOf(HtmlParentChunk::class, $actual);
        self::assertNotEmpty($actual->getChildren());
        self::assertCount(1, $actual);
        $actual = $actual->getChildren()[0];
        self::assertInstanceOf(HtmlLiChunk::class, $actual);
    }

    public function testNoBody(): void
    {
        $parser = new HtmlParser('<html></html>');
        $actual = $parser->parse();
        self::assertNull($actual);
    }

    public function testOlChunk(): void
    {
        $parser = new HtmlParser('<body><ol></ol></body>');
        $actual = $parser->parse();
        self::assertInstanceOf(HtmlParentChunk::class, $actual);
        self::assertNotEmpty($actual->getChildren());
        self::assertCount(1, $actual);
        $actual = $actual->getChildren()[0];
        self::assertInstanceOf(HtmlOlChunk::class, $actual);
    }

    public function testPageBreakChunk(): void
    {
        $parser = new HtmlParser('<body><div class="page-break"></div></body>');
        $actual = $parser->parse();
        self::assertInstanceOf(HtmlParentChunk::class, $actual);
        self::assertNotEmpty($actual->getChildren());
        self::assertCount(1, $actual);
        $actual = $actual->getChildren()[0];
        self::assertInstanceOf(HtmlPageBreakChunk::class, $actual);
    }

    public function testTextChunk(): void
    {
        $parser = new HtmlParser('<body><p>My Text</p></body>');
        $actual = $parser->parse();
        self::assertInstanceOf(HtmlParentChunk::class, $actual);
        self::assertNotEmpty($actual->getChildren());
        self::assertCount(1, $actual);

        $actual = $actual->getChildren()[0];
        self::assertInstanceOf(HtmlParentChunk::class, $actual);
        self::assertNotEmpty($actual->getChildren());
        self::assertCount(1, $actual);

        $actual = $actual->getChildren()[0];
        self::assertInstanceOf(HtmlTextChunk::class, $actual);
    }

    public function testUlChunk(): void
    {
        $parser = new HtmlParser('<body><ul></ul></body>');
        $actual = $parser->parse();
        self::assertInstanceOf(HtmlParentChunk::class, $actual);
        self::assertNotEmpty($actual->getChildren());
        self::assertCount(1, $actual);
        $actual = $actual->getChildren()[0];
        self::assertInstanceOf(HtmlUlChunk::class, $actual);
    }
}
