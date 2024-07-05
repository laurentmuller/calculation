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
use App\Pdf\Html\HtmlTag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractHtmlChunk::class)]
#[CoversClass(HtmlBrChunk::class)]
class HtmlBrChunkTest extends TestCase
{
    public function testIsNewLine(): void
    {
        $chunk = new HtmlBrChunk(HtmlTag::LINE_BREAK->value);
        self::assertTrue($chunk->isNewLine());
    }
}
