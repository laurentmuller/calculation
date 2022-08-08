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

use App\Pdf\Html\HtmlListType;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for {@link HtmlListType} enumeration.
 */
class HtmlListTypeTest extends TestCase
{
    public function getLetterValues(): array
    {
        return [
            [-1, ''],
            [0, ''],
            [1, 'A'],
            [26, 'Z'],
            [27, 'AA'],
            [53, 'AAA'],
        ];
    }

    public function getRomanValues(): array
    {
        return [
            [-1, ''],
            [0, ''],
            [5000, ''],

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
     * @dataProvider getLetterValues
     */
    public function testLetter(int $value, string $expected): void
    {
        $actual = HtmlListType::toLetter($value);
        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider getLetterValues
     */
    public function testLetterLower(int $value, string $expected): void
    {
        $actual = HtmlListType::LETTER_LOWER->getBulletText($value, '');
        self::assertEquals(\strtolower($expected), $actual);
    }

    /**
     * @dataProvider getLetterValues
     */
    public function testLetterUpper(int $value, string $expected): void
    {
        $actual = HtmlListType::LETTER_UPPER->getBulletText($value, '');
        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider getRomanValues
     */
    public function testRoman(int $value, string $expected): void
    {
        $actual = HtmlListType::toRoman($value);
        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider getRomanValues
     */
    public function testRomanLower(int $value, string $expected): void
    {
        $actual = HtmlListType::ROMAN_LOWER->getBulletText($value, '');
        self::assertEquals(\strtolower($expected), $actual);
    }

    /**
     * @dataProvider getRomanValues
     */
    public function testRomanUpper(int $value, string $expected): void
    {
        $actual = HtmlListType::ROMAN_UPPER->getBulletText($value, '');
        self::assertEquals($expected, $actual);
    }
}
