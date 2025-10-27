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

use App\Pdf\Html\HtmlDescriptionListChunk;
use App\Pdf\Html\HtmlParentChunk;
use App\Pdf\Html\HtmlTag;
use App\Pdf\Html\HtmlTextChunk;
use PHPUnit\Framework\TestCase;

final class HtmlDescriptionListChunkTest extends TestCase
{
    public function testAdd(): void
    {
        $chunk = new HtmlDescriptionListChunk();
        self::assertCount(0, $chunk);

        $chunk->add(new HtmlTextChunk());
        self::assertCount(0, $chunk);

        $chunk->add(new HtmlParentChunk(HtmlTag::DESCRIPTION_TERM));
        self::assertCount(1, $chunk);

        $chunk->add(new HtmlParentChunk(HtmlTag::DESCRIPTION_DETAIL));
        self::assertCount(2, $chunk);
    }
}
