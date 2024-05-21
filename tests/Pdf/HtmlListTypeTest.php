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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlListType::class)]
class HtmlListTypeTest extends TestCase
{
    public static function getLetterValues(): \Iterator
    {
        yield [-1, ''];
        yield [-1, '', '.'];
        yield [0, ''];
        yield [0, '', '.'];
        yield [1, 'A'];
        yield [1, 'A.', '.'];
        yield [26, 'Z'];
        yield [27, 'AA'];
        yield [53, 'AAA'];
    }

    public static function getNumberValues(): \Iterator
    {
        yield [-1, ''];
        yield [-1, '', '.'];
        yield [0, ''];
        yield [0,  '', '.'];
        yield [1, '1'];
        yield [1,  '1.', '.'];
        yield [10, '10'];
        yield [10, '10.', '.'];
        yield [1000, '1000'];
        yield [1000, '1000.', '.'];
        yield [1000, '1000.suffix', '.suffix'];
    }

    public static function getRomanValues(): \Iterator
    {
        yield [-1, ''];
        yield [-1, '', '.'];
        yield [0, ''];
        yield [0, '', '.'];
        yield [4000, ''];
        yield [4000, '', '.'];
        yield [1000, 'M'];
        yield [1000, 'M.', '.'];
        yield [900, 'CM'];
        yield [500, 'D'];
        yield [400, 'CD'];
        yield [100, 'C'];
        yield [90, 'XC'];
        yield [50, 'L'];
        yield [40, 'XL'];
        yield [10, 'X'];
        yield [9, 'IX'];
        yield [5, 'V'];
        yield [4, 'IV'];
        yield [1, 'I'];
        yield [8, 'VIII'];
        yield [13, 'XIII'];
        yield [14, 'XIV'];
        yield [123, 'CXXIII'];
        yield [2004, 'MMIV'];
        yield [2355, 'MMCCCLV'];
        yield [3999, 'MMMCMXCIX'];
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
