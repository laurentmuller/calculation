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

use App\Pdf\Html\HtmlLiChunk;
use App\Pdf\Html\HtmlParentChunk;
use App\Pdf\Html\HtmlTag;
use App\Pdf\Html\HtmlTextChunk;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTextChunk::class)]
class HtmlTextChunkTest extends TestCase
{
    public function testIsNewLine(): void
    {
        $chunk = new HtmlTextChunk(HtmlTag::TEXT->value);
        $chunk->setText('Text');

        $actual = $chunk->isNewLine();
        self::assertFalse($actual);

        $parent = new HtmlParentChunk(HtmlTag::PARAGRAPH->value);
        $parent->add($chunk);
        $actual = $chunk->isNewLine();
        self::assertFalse($actual);

        $liChunk = new HtmlLiChunk(HtmlTag::LIST_ORDERED->value);
        $liChunk->add($chunk);
        $liChunk->add(new HtmlLiChunk(HtmlTag::LIST_ORDERED->value));

        $parent = new HtmlParentChunk(HtmlTag::PARAGRAPH->value);
        $parent->add($liChunk);

        $actual = $chunk->isNewLine();
        self::assertTrue($actual);
    }
}
