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
    public static function getAscii(): \Iterator
    {
        yield ['home', 'home'];
        yield ['नमस्ते', 'namaste'];
        yield ['さよなら', 'sayonara'];
        yield ['спасибо', 'spasibo'];
    }

    public static function getCapitalize(): \Iterator
    {
        yield ['home', 'Home'];
        yield ['hOmE', 'Home'];
        yield ['my home', 'My home'];
        yield ['my Home', 'My home'];
        yield ['my HOME', 'My home'];
    }

    public static function getContains(): \Iterator
    {
        yield ['fake', '', false, false];
        yield ['before ab after', 'ab', false, true];
        yield ['before AB after', 'ab', false, false];
        yield ['before AB after', 'ab', true, true];
    }

    public static function getEndWith(): \Iterator
    {
        yield ['fake', '', false, false];
        yield ['fake', 'ke', false, true];
        yield ['fake', 'KE', false, false];
        yield ['fake', 'KE', true, true];
    }

    public static function getEqualIgnoreCase(): \Iterator
    {
        yield ['home', 'Home'];
        yield ['home', 'HOME'];
        yield ['a', 'b', false];
    }

    public static function getExportVar(): \Iterator
    {
        yield [null, 'NULL'];
        yield [true, 'true'];
        yield [false, 'false'];
        yield [0, '0'];
        yield [0.0, '0.0'];
        yield [0.01, '0.01'];
        yield [1000, '1000'];
        yield ['fake', "'fake'"];
        yield [['key' => 'value'], self::getVarArray()];
    }

    public static function getIsString(): \Iterator
    {
        yield [null, false];
        yield ['', false];
        yield ['my home', true];
    }

    public static function getShortName(): \Iterator
    {
        yield [null, null, true];
        yield [self::class, 'StringUtilsTest'];
        yield [Calculation::class, 'Calculation'];
        yield [new Calculation(), 'Calculation'];
        yield ['invalid argument', null, true];
    }

    public static function getStartWith(): \Iterator
    {
        yield ['fake', '', false, false];
        yield ['fake', 'fa', false, true];
        yield ['fake', 'FA', false, false];
        yield ['fake', 'FA', true, true];
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

    public function testDecodeJsonArray(): void
    {
        $expected = ['key' => 'value'];
        /** @psalm-var string $encoded */
        $encoded = \json_encode($expected);
        $actual = StringUtils::decodeJson($encoded);
        self::assertSame($expected, $actual);
    }

    public function testDecodeJsonObject(): void
    {
        $expected = new \stdClass();
        $expected->key = 'value';
        $expected->date = 'date';
        /** @psalm-var string $encoded */
        $encoded = \json_encode($expected);
        $actual = StringUtils::decodeJson($encoded, false);
        self::assertObjectHasProperty('key', $actual);
        self::assertObjectHasProperty('date', $actual);
        self::assertSame($expected->key, $actual->key);
        self::assertSame($expected->date, $actual->date);
        self::assertEqualsCanonicalizing($expected, $actual);
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

    public function testSlug(): void
    {
        $actual = StringUtils::slug('Wôrķšƥáçè ~~sèťtïñğš~~');
        self::assertSame('Workspace-settings', $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getStartWith')]
    public function testStartWith(string $haystack, string $needle, bool $ignore_case, bool $expected): void
    {
        $result = StringUtils::startWith($haystack, $needle, $ignore_case);
        self::assertSame($expected, $result);
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
