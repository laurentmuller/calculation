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

class HtmlUlChunkTest extends TestCase
{
    public function testAdd(): void
    {
        $ulChunk = new HtmlUlChunk('ul');
        self::assertCount(0, $ulChunk);
        $ulChunk->add(new HtmlBrChunk('br'));
        self::assertCount(0, $ulChunk);
        $ulChunk->add(new HtmlLiChunk('li'));
        self::assertCount(1, $ulChunk);
    }

    public function testBulletLast(): void
    {
        $ulChunk = new HtmlUlChunk('name');
        $actual = $ulChunk->getBulletLast();
        self::assertSame(\chr(149), $actual);
    }

    public function testBulletText(): void
    {
        $ulChunk = new HtmlUlChunk('ul');
        $liChunk = new HtmlLiChunk('li');
        $actual = $ulChunk->getBulletText($liChunk);
        self::assertSame(\chr(149), $actual);
    }
}
