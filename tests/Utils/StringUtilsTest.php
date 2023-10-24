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

use App\Entity\Calculation;
use App\Utils\StringUtils;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(StringUtils::class)]
class StringUtilsTest extends TestCase
{
    /**
     * @return array<array{0: string, 1: string}>
     */
    public static function getAscii(): array
    {
        return [
            ['home', 'home'],
            ['नमस्ते', 'namaste'],
            ['さよなら', 'sayonara'],
            ['спасибо', 'spasibo'],
        ];
    }

    /**
     * @return array<array{0: string, 1: string}>
     */
    public static function getCapitalize(): array
    {
        return [
            ['home', 'Home'],
            ['hOmE', 'Home'],
            ['my home', 'My home'],
            ['my Home', 'My home'],
            ['my HOME', 'My home'],
        ];
    }

    /**
     * @return array<array{0: string, 1: string, 2: bool, 3: bool}>
     */
    public static function getContains(): array
    {
        return [
            ['fake', '', false, false],
            ['before ab after', 'ab', false, true],
            ['before AB after', 'ab', false, false],
            ['before AB after', 'ab', true, true],
        ];
    }

    /**
     * @return array<array{0: string, 1: string, 2: bool, 3: bool}>
     */
    public static function getEndWith(): array
    {
        return [
            ['fake', '', false, false],
            ['fake', 'ke', false, true],
            ['fake', 'KE', false, false],
            ['fake', 'KE', true, true],
        ];
    }

    /**
     * @return array<array{0: string, 1: string, 2?: bool}>
     */
    public static function getEqualIgnoreCase(): array
    {
        return [
            ['home', 'Home'],
            ['home', 'HOME'],
            ['a', 'b', false],
        ];
    }

    /**
     * @return array<array{0: mixed, 1: mixed}>
     */
    public static function getExportVar(): array
    {
        return [
            [null, 'NULL'],
            [true, 'true'],
            [false, 'false'],
            [0, '0'],
            [0.0, '0.0'],
            [0.01, '0.01'],
            [1000, '1000'],
            ['fake', "'fake'"],
            [['key' => 'value'], self::getVarArray()],
        ];
    }

    /**
     * @return array<array{0: string|null, 1: bool}>
     */
    public static function getIsString(): array
    {
        return [
            [null, false],
            ['', false],
            ['my home', true],
        ];
    }

    /**
     * @return array<array{0: object|string|null, 1: string|null, 2?: bool}>
     */
    public static function getShortName(): array
    {
        return [
            [null, null, true],
            [self::class, 'StringUtilsTest'],
            [Calculation::class, 'Calculation'],
            [new Calculation(), 'Calculation'],
            ['invalid argument', null, true],
        ];
    }

    /**
     * @return array<array{0: string, 1: string, 2: bool, 3: bool}>
     */
    public static function getStartWith(): array
    {
        return [
            ['fake', '', false, false],
            ['fake', 'fa', false, true],
            ['fake', 'FA', false, false],
            ['fake', 'FA', true, true],
        ];
    }

    /**
     * @return array<array{0: mixed, 1: string, 2: bool}>
     */
    public static function getToString(): array
    {
        return [
            [null, '', true],
            [0, '0', true],
            [1.0, '0', false],
            ['a', '1', false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getAscii')]
    public function testAscii(string $value, string $expected): void
    {
        $result = StringUtils::ascii($value);
        self::assertSame($expected, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getCapitalize')]
    public function testCapitalize(string $value, string $expected): void
    {
        $result = StringUtils::capitalize($value);
        self::assertSame($expected, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getContains')]
    public function testContains(string $haystack, string $needle, bool $ignore_case, bool $expected): void
    {
        $result = StringUtils::contains($haystack, $needle, $ignore_case);
        self::assertSame($expected, $result);
    }

    public function testDecodeJson(): void
    {
        $expected = ['key' => 'value'];
        /** @psalm-var string $encoded */
        $encoded = \json_encode($expected);
        $actual = StringUtils::decodeJson($encoded);
        self::assertSame($expected, $actual);
    }

    public function testDecodeJsonWidthException(): void
    {
        self::expectException(\InvalidArgumentException::class);
        StringUtils::decodeJson('{"key":}');
        self::fail("An \InvalidArgumentException must be throw.");
    }

    public function testEncodeJson(): void
    {
        $expected = '{"key":"value"}';
        /** @psalm-var array $decoded */
        $decoded = \json_decode($expected, true);
        $actual = StringUtils::encodeJson($decoded);
        self::assertSame($expected, $actual);
    }

    public function testEncodeJsonWidthException(): void
    {
        self::expectException(\InvalidArgumentException::class);
        $input = \mb_convert_encoding('ø, æ, å', 'ISO-8859-1');
        StringUtils::encodeJson($input);
        self::fail("An \InvalidArgumentException must be throw.");
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getEndWith')]
    public function testEndWith(string $haystack, string $needle, bool $ignore_case, bool $expected): void
    {
        $result = StringUtils::endWith($haystack, $needle, $ignore_case);
        self::assertSame($expected, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getEqualIgnoreCase')]
    public function testEqualIgnoreCase(string $string1, string $string2, bool $expected = true): void
    {
        $result = StringUtils::equalIgnoreCase($string1, $string2);
        self::assertSame($result, $expected);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getExportVar')]
    public function testExportVar(mixed $var, mixed $expected): void
    {
        $result = StringUtils::exportVar($var);
        self::assertSame($expected, $result);
    }

    /**
     * @psalm-param object|class-string|null $var
     *
     * @psalm-suppress PossiblyNullArgument
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getShortName')]
    public function testGetShortName(object|string|null $var, mixed $expected, bool $exception = false): void
    {
        if (null === $var) {
            $this->expectException(\TypeError::class);
        } elseif ($exception) {
            $this->expectException(\RuntimeException::class);
        }
        $result = StringUtils::getShortName($var); /* @phpstan-ignore-line */
        self::assertSame($expected, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getIsString')]
    public function testIsString(?string $var, bool $expected): void
    {
        $result = StringUtils::isString($var);
        self::assertSame($expected, $result);
    }

    public function testNewLine(): void
    {
        self::assertSame("\n", StringUtils::NEW_LINE);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getStartWith')]
    public function testStartWith(string $haystack, string $needle, bool $ignore_case, bool $expected): void
    {
        $result = StringUtils::startWith($haystack, $needle, $ignore_case);
        self::assertSame($expected, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getToString')]
    public function testToString(mixed $var, string $expected, bool $equal): void
    {
        $result = StringUtils::toString($var);
        if ($equal) {
            self::assertSame($result, $expected);
        } else {
            self::assertNotSame($result, $expected);
        }
    }

    private static function getVarArray(): string
    {
        return <<<ARRAY
            [
              'key' => 'value'
            ]
            ARRAY;
    }
}
