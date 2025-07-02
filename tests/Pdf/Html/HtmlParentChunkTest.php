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

use App\Pdf\Html\HtmlPageBreakChunk;
use App\Pdf\Html\HtmlParentChunk;
use App\Pdf\Html\HtmlTag;
use PHPUnit\Framework\TestCase;

class HtmlParentChunkTest extends TestCase
{
    public function testAdd(): void
    {
        $actual = new HtmlParentChunk(HtmlTag::BODY);
        $chunk = new HtmlPageBreakChunk();
        $actual->add($chunk);
        self::assertCount(1, $actual);

        $actual->add($chunk);
        self::assertCount(1, $actual);
    }

    public function testDefault(): void
    {
        $actual = new HtmlParentChunk(HtmlTag::BODY);
        self::assertCount(0, $actual);
        self::assertCount(0, $actual->getChildren());
        self::assertNull($actual->getParent());
        self::assertNull($actual->findChild(HtmlTag::BOLD));
        self::assertTrue($actual->isEmpty());
        self::assertFalse($actual->isNewLine());

        $chunk = new HtmlPageBreakChunk();
        self::assertSame(-1, $actual->indexOf($chunk));
    }

    public function testFind(): void
    {
        $parent = new HtmlParentChunk(HtmlTag::BODY);
        $actual = $parent->findChild(HtmlTag::H1);
        self::assertNull($actual);

        $span = new HtmlParentChunk(HtmlTag::SPAN);
        $parent->add($span);

        $pageBreak = new HtmlPageBreakChunk();
        $parent->add($pageBreak);

        $actual = $parent->findChild(HtmlTag::SPAN);
        self::assertNotNull($actual);

        $actual = $parent->findChild(HtmlTag::PAGE_BREAK);
        self::assertNotNull($actual);

        $actual = $parent->findChild(HtmlTag::H1);
        self::assertNull($actual);
    }

    public function testFindRecursive(): void
    {
        $parent = new HtmlParentChunk(HtmlTag::BODY);
        $actual = $parent->findChild(HtmlTag::BOLD);
        self::assertNull($actual);

        $h1 = new HtmlParentChunk(HtmlTag::H1);
        $parent->add($h1);

        $h2 = new HtmlParentChunk(HtmlTag::H2);
        $h1->add($h2);

        $actual = $parent->findChild(HtmlTag::H1);
        self::assertNotNull($actual);

        $actual = $parent->findChild(HtmlTag::H2);
        self::assertNotNull($actual);
    }

    public function testRemove(): void
    {
        $actual = new HtmlParentChunk(HtmlTag::BODY);
        $chunk = new HtmlPageBreakChunk();

        $actual->add($chunk);
        self::assertCount(1, $actual);
        $actual->remove($chunk);
        self::assertCount(0, $actual);

        $actual->remove($chunk);
        self::assertCount(0, $actual);
    }
}
