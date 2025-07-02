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

use App\Pdf\Html\AbstractHtmlChunk;
use App\Pdf\Html\HtmlBrChunk;
use App\Pdf\Html\HtmlLiChunk;
use App\Pdf\Html\HtmlOlChunk;
use App\Pdf\Html\HtmlPageBreakChunk;
use App\Pdf\Html\HtmlParentChunk;
use App\Pdf\Html\HtmlParser;
use App\Pdf\Html\HtmlTextChunk;
use App\Pdf\Html\HtmlUlChunk;
use PHPUnit\Framework\TestCase;

class HtmlParserTest extends TestCase
{
    public function testBrChunk(): void
    {
        $parser = new HtmlParser('<body><br></body>');
        $actual = $parser->parse();
        self::assertChunks($actual, HtmlParentChunk::class, HtmlBrChunk::class);
    }

    public function testDivChunk(): void
    {
        $parser = new HtmlParser('<body><div></div></body>');
        $actual = $parser->parse();
        self::assertChunks($actual, HtmlParentChunk::class);
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
        self::assertChunks($actual, HtmlParentChunk::class, HtmlLiChunk::class);
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
        self::assertChunks($actual, HtmlParentChunk::class, HtmlOlChunk::class);
    }

    public function testPageBreakChunk(): void
    {
        $parser = new HtmlParser('<body><div class="page-break"></div></body>');
        $actual = $parser->parse();
        self::assertChunks($actual, HtmlParentChunk::class, HtmlPageBreakChunk::class);
    }

    public function testTextChunk(): void
    {
        $parser = new HtmlParser('<body><p>My Text</p></body>');
        $actual = $parser->parse();
        self::assertChunks($actual, HtmlParentChunk::class, HtmlParentChunk::class, HtmlTextChunk::class);
    }

    public function testUlChunk(): void
    {
        $parser = new HtmlParser('<body><ul></ul></body>');
        $actual = $parser->parse();
        self::assertChunks($actual, HtmlParentChunk::class, HtmlUlChunk::class);
    }

    /**
     * @phpstan-param class-string<AbstractHtmlChunk> ...$classes
     */
    protected static function assertChunks(mixed $actual, string ...$classes): void
    {
        $index = 0;
        $last = \count($classes) - 1;
        foreach ($classes as $class) {
            self::assertInstanceOf($class, $actual);
            if ($index < $last && $actual instanceof HtmlParentChunk) {
                self::assertNotEmpty($actual->getChildren());
                self::assertCount(1, $actual->getChildren());
                $actual = $actual->getChildren()[0];
            }
            ++$index;
        }
    }
}
