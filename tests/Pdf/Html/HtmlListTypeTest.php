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

final class HtmlListTypeTest extends TestCase
{
    /**
     * @phpstan-return \Generator<int, array{0: positive-int, 1: string, 2?: string}>
     */
    public static function getLetterLower(): \Generator
    {
        yield [1, 'a'];
        yield [1, 'a.', '.'];
        yield [26, 'z'];
        yield [27, 'aa'];
        yield [53, 'aaa'];

        yield [1, 'a.lower', '.lower'];
        yield [1, 'a.UPPER', '.UPPER'];
    }

    /**
     * @phpstan-return \Generator<int, array{0: positive-int, 1: string, 2?: string}>
     */
    public static function getLetterUpper(): \Generator
    {
        yield [1, 'A'];
        yield [1, 'A.', '.'];
        yield [26, 'Z'];
        yield [27, 'AA'];
        yield [53, 'AAA'];

        yield [1, 'A.lower', '.lower'];
        yield [1, 'A.UPPER', '.UPPER'];
    }

    /**
     * @phpstan-return \Generator<int, array{0: positive-int, 1: string, 2?: string}>
     */
    public static function getNumberValues(): \Generator
    {
        yield [1, '1'];
        yield [1,  '1.', '.'];
        yield [10, '10'];
        yield [10, '10.', '.'];
        yield [1000, '1000'];
        yield [1000, '1000.', '.'];
        yield [1000, '1000.lower', '.lower'];
        yield [1000, '1000.UPPER', '.UPPER'];
    }

    /**
     * @phpstan-return \Generator<int, array{0: positive-int, 1: string, 2?: string}>
     */
    public static function getRomanLower(): \Generator
    {
        yield [4000, ''];
        yield [4000, '', '.'];
        yield [1000, 'm'];
        yield [1000, 'm.', '.'];
        yield [900, 'cm'];
        yield [500, 'd'];
        yield [400, 'cd'];
        yield [100, 'c'];
        yield [90, 'xc'];
        yield [50, 'l'];
        yield [40, 'xl'];
        yield [10, 'x'];
        yield [9, 'ix'];
        yield [5, 'v'];
        yield [4, 'iv'];
        yield [1, 'i'];
        yield [8, 'viii'];
        yield [13, 'xiii'];
        yield [14, 'xiv'];
        yield [123, 'cxxiii'];
        yield [2004, 'mmiv'];
        yield [2355, 'mmccclv'];
        yield [3999, 'mmmcmxcix'];

        yield [1, 'i.lower', '.lower'];
        yield [1, 'i.UPPER', '.UPPER'];
    }

    /**
     * @phpstan-return \Generator<int, array{0: positive-int, 1: string, 2?: string}>
     */
    public static function getRomanUpper(): \Generator
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

        yield [1, 'I.lower', '.lower'];
        yield [1, 'I.UPPER', '.UPPER'];
    }

    /**
     * @phpstan-param positive-int $value
     */
    #[DataProvider('getLetterLower')]
    public function testLetterLower(int $value, string $expected, string $suffix = ''): void
    {
        $actual = HtmlListType::LETTER_LOWER->getBulletText($value, $suffix);
        self::assertSame($expected, $actual);
    }

    /**
     * @phpstan-param positive-int $value
     */
    #[DataProvider('getLetterUpper')]
    public function testLetterUpper(int $value, string $expected, string $suffix = ''): void
    {
        $actual = HtmlListType::LETTER_UPPER->getBulletText($value, $suffix);
        self::assertSame($expected, $actual);
    }

    /**
     * @phpstan-param positive-int $value
     */
    #[DataProvider('getNumberValues')]
    public function testNumber(int $value, string $expected, string $suffix = ''): void
    {
        $actual = HtmlListType::NUMBER->getBulletText($value, $suffix);
        self::assertSame($expected, $actual);
    }

    /**
     * @phpstan-param positive-int $value
     */
    #[DataProvider('getRomanLower')]
    public function testRomanLower(int $value, string $expected, string $suffix = ''): void
    {
        $actual = HtmlListType::ROMAN_LOWER->getBulletText($value, $suffix);
        self::assertSame($expected, $actual);
    }

    /**
     * @phpstan-param positive-int $value
     */
    #[DataProvider('getRomanUpper')]
    public function testRomanUpper(int $value, string $expected, string $suffix = ''): void
    {
        $actual = HtmlListType::ROMAN_UPPER->getBulletText($value, $suffix);
        self::assertSame($expected, $actual);
    }
}
