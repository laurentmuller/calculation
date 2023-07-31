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

#[\PHPUnit\Framework\Attributes\CoversClass(HtmlListType::class)]
class HtmlListTypeTest extends TestCase
{
    public static function getLetterValues(): array
    {
        return [
            [-1, ''],
            [-1, '', '.'],
            [0, ''],
            [0, '', '.'],
            [1, 'A'],
            [1, 'A.', '.'],
            [26, 'Z'],
            [27, 'AA'],
            [53, 'AAA'],
        ];
    }

    public static function getNumberValues(): array
    {
        return [
            [-1, ''],
            [-1, '', '.'],
            [0, ''],
            [0,  '', '.'],
            [1, '1'],
            [1,  '1.', '.'],
            [10, '10'],
            [10, '10.', '.'],
            [1000, '1000'],
            [1000, '1000.', '.'],
            [1000, '1000.suffix', '.suffix'],
        ];
    }

    public static function getRomanValues(): array
    {
        return [
            [-1, ''],
            [-1, '', '.'],
            [0, ''],
            [0, '', '.'],
            [5000, ''],
            [5000, '', '.'],

            [1000, 'M'],
            [1000, 'M.', '.'],
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

    #[\PHPUnit\Framework\Attributes\DataProvider('getLetterValues')]
    public function testLetterLower(int $value, string $expected, string $suffix = ''): void
    {
        $actual = HtmlListType::LETTER_LOWER->getBulletText($value, $suffix);
        self::assertSame(\strtolower($expected), $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getLetterValues')]
    public function testLetterUpper(int $value, string $expected, string $suffix = ''): void
    {
        $actual = HtmlListType::LETTER_UPPER->getBulletText($value, $suffix);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getNumberValues')]
    public function testNumber(int $value, string $expected, string $suffix = ''): void
    {
        $actual = HtmlListType::NUMBER->getBulletText($value, $suffix);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getRomanValues')]
    public function testRomanLower(int $value, string $expected, string $suffix = ''): void
    {
        $actual = HtmlListType::ROMAN_LOWER->getBulletText($value, $suffix);
        self::assertSame(\strtolower($expected), $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getRomanValues')]
    public function testRomanUpper(int $value, string $expected, string $suffix = ''): void
    {
        $actual = HtmlListType::ROMAN_UPPER->getBulletText($value, $suffix);
        self::assertSame($expected, $actual);
    }
}
