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

/**
 * Unit test for {@link PdfBorder}.
 */
class PdfBorderTest extends TestCase
{
    public function getBorders(): array
    {
        return [
            [-1, PdfBorder::INHERITED],
            [0, PdfBorder::NONE],
            [1, PdfBorder::ALL],

            ['F', PdfBorder::FILL],
            ['D', PdfBorder::BORDER],
            ['FD', PdfBorder::BOTH],

            ['L', PdfBorder::LEFT],
            ['R', PdfBorder::RIGHT],
            ['T', PdfBorder::TOP],
            ['B', PdfBorder::BOTTOM],

            ['', PdfBorder::NONE],
            [1000, PdfBorder::NONE],
            [-2, PdfBorder::NONE],

            ['LR', 'LR'],
            ['RL', 'LR'],
            ['LRTB', 'BLRT'],
            ['RTBL', 'BLRT'],

            ['TR', 'RT'],
        ];
    }

    /**
     * @dataProvider getBorders
     */
    public function testBorder(string|int $value, string|int $expected): void
    {
        $broder = new PdfBorder($value);
        $this->assertSame($expected, $broder->getValue());
    }
}
