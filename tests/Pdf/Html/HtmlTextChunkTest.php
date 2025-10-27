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

use App\Pdf\Html\HtmlOlChunk;
use App\Pdf\Html\HtmlParentChunk;
use App\Pdf\Html\HtmlStyle;
use App\Pdf\Html\HtmlTag;
use App\Pdf\Html\HtmlTextChunk;
use PHPUnit\Framework\TestCase;

final class HtmlTextChunkTest extends TestCase
{
    public function testHtmlBookmark(): void
    {
        $chunk = new HtmlTextChunk();
        self::assertFalse($chunk->isBookmark());
        $chunk->setClassName('bookmark');
        self::assertTrue($chunk->isBookmark());
    }

    public function testIsNewLine(): void
    {
        $chunk = new HtmlTextChunk();
        $actual = $chunk->isNewLine();
        self::assertFalse($actual);

        $parent = new HtmlParentChunk(HtmlTag::PARAGRAPH);
        $parent->add($chunk);
        $actual = $chunk->isNewLine();
        self::assertFalse($actual);

        $parent->add(new HtmlOlChunk());
        $actual = $chunk->isNewLine();
        self::assertTrue($actual);
    }

    public function testStyle(): void
    {
        $chunk = new HtmlTextChunk(text: 'Text');
        self::assertNull($chunk->getStyle());
        self::assertFalse($chunk->hasStyle());
        $chunk->setStyle(HtmlStyle::default());
        self::assertNotNull($chunk->getStyle());
        self::assertTrue($chunk->hasStyle());
    }
}
