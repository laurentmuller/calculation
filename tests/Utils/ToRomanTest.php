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

namespace App\Tests\Utils;

use App\Pdf\Html\HtmlOlChunk;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for convert integer to roman.
 *
 * @author Laurent Muller
 */
class ToRomanTest extends TestCase
{
    public function getValues(): array
    {
        return [
            [-1, '#N/A#'],
            [0, '#N/A#'],
            [5000, '#N/A#'],

            [1000, 'M'],
            [900, 'CM'],
            [500, 'D'],
            [400, 'CD'],
            [100, 'C'],
            [90, 'XC'],
            [50, 'L'],
            [40, 'XL'],
            [10, 'X'],
            [9, 'IX'],
            [5, 'V'],
            [4, 'IV'],
            [1, 'I'],

            [8, 'VIII'],
            [13, 'XIII'],
            [14, 'XIV'],
            [123, 'CXXIII'],
            [2004, 'MMIV'],
            [2355, 'MMCCCLV'],
            [4999, 'MMMMCMXCIX'],
        ];
    }

    /**
     * @dataProvider getValues
     */
    public function testValue(int $value, string $expected): void
    {
        $actual = HtmlOlChunk::toRoman($value);
        $this->assertEquals($expected, $actual);
    }
}
