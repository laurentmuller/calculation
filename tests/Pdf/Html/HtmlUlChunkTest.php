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
use App\Pdf\Html\HtmlUlChunk;
use PHPUnit\Framework\TestCase;

final class HtmlUlChunkTest extends TestCase
{
    public function testAdd(): void
    {
        $chunk = new HtmlUlChunk();
        self::assertCount(0, $chunk);
        $chunk->add(new HtmlBrChunk());
        self::assertCount(0, $chunk);
        $chunk->add(new HtmlLiChunk());
        self::assertCount(1, $chunk);
    }

    public function testBulletLast(): void
    {
        $chunk = new HtmlUlChunk();
        $actual = $chunk->getLastBulletText();
        self::assertSame(\chr(149), $actual);
    }

    public function testBulletText(): void
    {
        $ulChunk = new HtmlUlChunk();
        $liChunk = new HtmlLiChunk();
        $actual = $ulChunk->getBulletText($liChunk);
        self::assertSame(\chr(149), $actual);
    }
}
