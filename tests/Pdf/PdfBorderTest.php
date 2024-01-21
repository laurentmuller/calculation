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

use App\Pdf\PdfBorder;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(PdfBorder::class)]
class PdfBorderTest extends TestCase
{
    public static function getBorders(): \Iterator
    {
        yield [-1, PdfBorder::INHERITED];
        yield [0, PdfBorder::NONE];
        yield [1, PdfBorder::ALL];
        yield ['F', PdfBorder::FILL];
        yield ['D', PdfBorder::BORDER];
        yield ['FD', PdfBorder::BOTH];
        yield ['L', PdfBorder::LEFT];
        yield ['R', PdfBorder::RIGHT];
        yield ['T', PdfBorder::TOP];
        yield ['B', PdfBorder::BOTTOM];
        yield ['', PdfBorder::NONE];
        yield [1000, PdfBorder::NONE];
        yield [-2, PdfBorder::NONE];
        yield ['LR', 'LR'];
        yield ['TB', 'TB'];
        yield ['LR', 'LR'];
        yield ['TR', 'TR'];
        yield ['LRTB', PdfBorder::ALL];
        yield ['RTBL', PdfBorder::ALL];
        yield ['A', PdfBorder::NONE];
        yield ['RR', PdfBorder::RIGHT];
        yield ['RA', PdfBorder::RIGHT];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getBorders')]
    public function testBorder(string|int $value, string|int $expected): void
    {
        $border = new PdfBorder($value);
        $actual = $border->getValue();
        self::assertSame($expected, $actual);
    }
}
