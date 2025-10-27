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
use App\Pdf\Html\HtmlOlChunk;
use PHPUnit\Framework\TestCase;

final class HtmlOlChunkTest extends TestCase
{
    public function testGetBulletLast(): void
    {
        $olChunk = new HtmlOlChunk();
        $actual = $olChunk->getBulletLast();
        self::assertSame('1.', $actual);

        $olChunk->add(new HtmlLiChunk());
        $olChunk->add(new HtmlLiChunk());
        $actual = $olChunk->getBulletLast();
        self::assertSame('2.', $actual);
    }

    public function testGetBulletText(): void
    {
        $olChunk = new HtmlOlChunk();
        $liChunk1 = new HtmlLiChunk();
        $actual = $olChunk->getBulletText($liChunk1);
        self::assertSame('1.', $actual);

        $olChunk->add($liChunk1);
        $actual = $olChunk->getBulletText($liChunk1);
        self::assertSame('1.', $actual);

        $liChunk2 = new HtmlLiChunk();
        $olChunk->add($liChunk2);
        $actual = $olChunk->getBulletText($liChunk2);
        self::assertSame('2.', $actual);
    }
}
