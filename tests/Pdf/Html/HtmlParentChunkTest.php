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
use App\Tests\AssertEmptyTrait;
use PHPUnit\Framework\TestCase;

class HtmlParentChunkTest extends TestCase
{
    use AssertEmptyTrait;

    public function testAdd(): void
    {
        $actual = new HtmlParentChunk('body');
        $chunk = new HtmlPageBreakChunk('fake');
        $actual->add($chunk);
        self::assertCount(1, $actual);

        $actual->add($chunk);
        self::assertCount(1, $actual);
    }

    public function testDefault(): void
    {
        $actual = new HtmlParentChunk('body');
        self::assertEmptyCountable($actual);
        self::assertEmpty($actual->getChildren());
        self::assertNull($actual->getParent());
        self::assertNull($actual->findChild(HtmlTag::BOLD));
        self::assertTrue($actual->isEmpty());
        self::assertFalse($actual->isNewLine());

        $chunk = new HtmlPageBreakChunk('fake');
        self::assertSame(-1, $actual->indexOf($chunk));
    }

    public function testFind(): void
    {
        $parent = new HtmlParentChunk(HtmlTag::BODY->value);
        $actual = $parent->findChild(HtmlTag::H1);
        self::assertNull($actual);

        $span = new HtmlParentChunk(HtmlTag::SPAN->value);
        $parent->add($span);

        $pageBreak = new HtmlPageBreakChunk(HtmlTag::PAGE_BREAK->value);
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
        $parent = new HtmlParentChunk(HtmlTag::BODY->value);
        $actual = $parent->findChild(HtmlTag::BOLD);
        self::assertNull($actual);

        $h1 = new HtmlParentChunk(HtmlTag::H1->value);
        $parent->add($h1);

        $h2 = new HtmlParentChunk(HtmlTag::H2->value);
        $h1->add($h2);

        $actual = $parent->findChild(HtmlTag::H1);
        self::assertNotNull($actual);

        $actual = $parent->findChild(HtmlTag::H2);
        self::assertNotNull($actual);
    }

    public function testRemove(): void
    {
        $actual = new HtmlParentChunk(HtmlTag::BODY->value);
        $chunk = new HtmlPageBreakChunk(HtmlTag::PAGE_BREAK->value);

        $actual->add($chunk);
        self::assertCount(1, $actual);
        $actual->remove($chunk);
        self::assertEmptyCountable($actual);

        $actual->remove($chunk);
        self::assertEmptyCountable($actual);
    }
}
