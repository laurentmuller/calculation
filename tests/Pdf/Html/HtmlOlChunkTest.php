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

use App\Pdf\Html\AbstractHtmlListChunk;
use App\Pdf\Html\HtmlLiChunk;
use App\Pdf\Html\HtmlListType;
use App\Pdf\Html\HtmlOlChunk;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlOlChunk::class)]
#[CoversClass(AbstractHtmlListChunk::class)]
class HtmlOlChunkTest extends TestCase
{
    public function testGetBulletLast(): void
    {
        $olChunk = new HtmlOlChunk('ol');
        $actual = $olChunk->getBulletLast();
        self::assertSame('1.', $actual);

        $olChunk->add(new HtmlLiChunk('li'));
        $olChunk->add(new HtmlLiChunk('li'));
        $actual = $olChunk->getBulletLast();
        self::assertSame('2.', $actual);
    }

    public function testGetBulletText(): void
    {
        $olChunk = new HtmlOlChunk('ol');
        $liChunk1 = new HtmlLiChunk('li');
        $actual = $olChunk->getBulletText($liChunk1);
        self::assertSame('1.', $actual);

        $olChunk->add($liChunk1);
        $actual = $olChunk->getBulletText($liChunk1);
        self::assertSame('1.', $actual);

        $liChunk2 = new HtmlLiChunk('li');
        $olChunk->add($liChunk2);
        $actual = $olChunk->getBulletText($liChunk2);
        self::assertSame('2.', $actual);
    }

    public function testGetStart(): void
    {
        $olChunk = new HtmlOlChunk('ol');
        $actual = $olChunk->getStart();
        self::assertSame(1, $actual);

        $olChunk->setStart(2);
        $actual = $olChunk->getStart();
        self::assertSame(2, $actual);
    }

    public function testType(): void
    {
        $expected = HtmlListType::NUMBER;
        $olChunk = new HtmlOlChunk('ol');
        $actual = $olChunk->getType();
        self::assertSame($expected, $actual);

        $expected = HtmlListType::LETTER_LOWER;
        $olChunk->setType($expected);
        $actual = $olChunk->getType();
        self::assertSame($expected, $actual);
    }
}
