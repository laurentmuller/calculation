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

namespace App\Tests\Pdf;

use App\Pdf\PdfCell;
use App\Pdf\PdfStyle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PdfCellTest extends TestCase
{
    public static function getHasLinks(): \Generator
    {
        yield [null, false];
        yield ['', false];
        yield [0, false];
        yield [-1, false];

        yield ['link', true];
        yield [1, true];
    }

    public function testClone(): void
    {
        $style = PdfStyle::getCellStyle();
        $cell = new PdfCell(style: $style);
        self::assertSame($style, $cell->getStyle());

        $clone = clone $cell;
        self::assertNotSame($cell->getStyle(), $clone->getStyle());
    }

    public function testConstructor(): void
    {
        $cell = new PdfCell();
        self::assertNull($cell->getText());
        self::assertSame(1, $cell->getCols());
        self::assertNull($cell->getStyle());
        self::assertNull($cell->getAlignment());
        self::assertNull($cell->getLink());
        self::assertFalse($cell->hasLink());
    }

    #[DataProvider('getHasLinks')]
    public function testHasLink(string|int|null $link, bool $expected): void
    {
        $cell = new PdfCell(link: $link);
        self::assertSame($expected, $cell->hasLink());
    }
}
