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

use App\Pdf\Html\HtmlListType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HtmlListTypeTest extends TestCase
{
    /**
     * @psalm-return \Generator<int, array{0: positive-int, 1: string, 2?: string}>
     */
    public static function getLetterValues(): \Generator
    {
        yield [1, 'A'];
        yield [1, 'A.', '.'];
        yield [26, 'Z'];
        yield [27, 'AA'];
        yield [53, 'AAA'];
    }

    /**
     * @psalm-return \Generator<int, array{0: positive-int, 1: string, 2?: string}>
     */
    public static function getNumberValues(): \Generator
    {
        yield [1, '1'];
        yield [1,  '1.', '.'];
        yield [10, '10'];
        yield [10, '10.', '.'];
        yield [1000, '1000'];
        yield [1000, '1000.', '.'];
        yield [1000, '1000.suffix', '.suffix'];
    }

    /**
     * @psalm-return \Generator<int, array{0: positive-int, 1: string, 2?: string}>
     */
    public static function getRomanValues(): \Generator
    {
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

    /**
     * @psalm-param positive-int $value
     */
    #[DataProvider('getLetterValues')]
    public function testLetterLower(int $value, string $expected, string $suffix = ''): void
    {
        $expected = \strtolower($expected);
        $actual = HtmlListType::LETTER_LOWER->getBulletText($value, $suffix);
        self::assertSame($expected, $actual);
    }

    /**
     * @psalm-param positive-int $value
     */
    #[DataProvider('getLetterValues')]
    public function testLetterUpper(int $value, string $expected, string $suffix = ''): void
    {
        $actual = HtmlListType::LETTER_UPPER->getBulletText($value, $suffix);
        self::assertSame($expected, $actual);
    }

    /**
     * @psalm-param positive-int $value
     */
    #[DataProvider('getNumberValues')]
    public function testNumber(int $value, string $expected, string $suffix = ''): void
    {
        $actual = HtmlListType::NUMBER->getBulletText($value, $suffix);
        self::assertSame($expected, $actual);
    }

    /**
     * @psalm-param positive-int $value
     */
    #[DataProvider('getRomanValues')]
    public function testRomanLower(int $value, string $expected, string $suffix = ''): void
    {
        $expected = \strtolower($expected);
        $actual = HtmlListType::ROMAN_LOWER->getBulletText($value, $suffix);
        self::assertSame($expected, $actual);
    }

    /**
     * @psalm-param positive-int $value
     */
    #[DataProvider('getRomanValues')]
    public function testRomanUpper(int $value, string $expected, string $suffix = ''): void
    {
        $actual = HtmlListType::ROMAN_UPPER->getBulletText($value, $suffix);
        self::assertSame($expected, $actual);
    }
}
