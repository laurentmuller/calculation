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

use App\Pdf\Enums\PdfFontStyle;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(PdfFontStyle::class)]
class PdfFontStyleTest extends TestCase
{
    public static function getFrom(): array
    {
        return [
            ['B', PdfFontStyle::BOLD],
            ['BI', PdfFontStyle::BOLD_ITALIC],
            ['BIU', PdfFontStyle::BOLD_ITALIC_UNDERLINE],
            ['BU', PdfFontStyle::BOLD_UNDERLINE],
            ['I', PdfFontStyle::ITALIC],
            ['IU', PdfFontStyle::ITALIC_UNDERLINE],
            ['U', PdfFontStyle::UNDERLINE],
            ['', PdfFontStyle::REGULAR],

            ['b', PdfFontStyle::BOLD, true],
            ['Z', PdfFontStyle::REGULAR, true],
        ];
    }

    public static function getFromStyle(): array
    {
        return [
            ['b', PdfFontStyle::BOLD],
            ['B', PdfFontStyle::BOLD],

            ['bi', PdfFontStyle::BOLD_ITALIC],
            ['ib', PdfFontStyle::BOLD_ITALIC],

            ['biu', PdfFontStyle::BOLD_ITALIC_UNDERLINE],
            ['iub', PdfFontStyle::BOLD_ITALIC_UNDERLINE],
            ['ubi', PdfFontStyle::BOLD_ITALIC_UNDERLINE],
            ['uib', PdfFontStyle::BOLD_ITALIC_UNDERLINE],

            ['bu', PdfFontStyle::BOLD_UNDERLINE],
            ['uB', PdfFontStyle::BOLD_UNDERLINE],

            ['i', PdfFontStyle::ITALIC],
            ['I', PdfFontStyle::ITALIC],

            ['iu', PdfFontStyle::ITALIC_UNDERLINE],
            ['ui', PdfFontStyle::ITALIC_UNDERLINE],

            ['u', PdfFontStyle::UNDERLINE],
            ['U', PdfFontStyle::UNDERLINE],

            [null, PdfFontStyle::REGULAR],
            ['', PdfFontStyle::REGULAR],
            ['z', PdfFontStyle::REGULAR],

            ['BBB', PdfFontStyle::BOLD],
            ['BIBI', PdfFontStyle::BOLD_ITALIC],
            ['bibi', PdfFontStyle::BOLD_ITALIC],
        ];
    }

    public function testDefault(): void
    {
        $expected = PdfFontStyle::REGULAR;
        $actual = PdfFontStyle::getDefault();
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getFrom')]
    public function testFrom(string $style, PdfFontStyle $expected, bool $exception = false): void
    {
        if ($exception) {
            self::expectException(\ValueError::class);
        }
        $actual = PdfFontStyle::from($style);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getFromStyle')]
    public function testFromStyle(?string $style, PdfFontStyle $expected): void
    {
        $actual = PdfFontStyle::fromStyle($style);
        self::assertSame($expected, $actual);
    }
}
