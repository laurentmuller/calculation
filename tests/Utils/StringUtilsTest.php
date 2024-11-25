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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

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

    public static function getPregMatch(): \Iterator
    {
        yield ['/\d+/', '1234', true];
        yield ['/\d+/', 'FAKE', false];
        yield ['/(?J)(?<match>foo)|(?<match>bar)/', 'foo bar', true];
        yield ['/(foo)(bar)(baz)/', 'foobarbaz', true];
        yield ['/(foo)(bar)(baz)/', 'foobaz', false];
    }

    public static function getPregMatchAll(): \Iterator
    {
        yield ['/\d+/', '1234', true];
        yield ['/\d+/', 'FAKE', false];

        yield ['/(?J)(?<match>foo)|(?<match>bar)/', 'foo bar', true];
        yield ['/(foo)(bar)(baz)/', 'foobarbaz', true];
        yield ['/(foo)(bar)(baz)/', 'foobaz', false];
    }

    public static function getPregReplace(): \Iterator
    {
        yield ['/\d+/', '', '1234', ''];
        yield ['/\d+/', '', 'FAKE', 'FAKE'];
    }

    public static function getPregReplaceAll(): \Iterator
    {
        yield [['/\d+/' => ''], '1234', ''];
        yield [['/\d+/' => ''], 'FAKE', 'FAKE'];
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

    public static function getTrim(): \Iterator
    {
        yield ['', null];
        yield [' ', null];
        yield ['fake', 'fake'];
        yield [' fake', 'fake'];
        yield ['fake ', 'fake'];
        yield [' fake ', 'fake'];
    }

    #[DataProvider('getAscii')]
    public function testAscii(string $value, string $expected): void
    {
        $actual = StringUtils::ascii($value);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getCapitalize')]
    public function testCapitalize(string $value, string $expected): void
    {
        $actual = StringUtils::capitalize($value);
        self::assertSame($expected, $actual);
    }

    public function testDecodeJsonArray(): void
    {
        $expected = ['key' => 'value'];
        $encoded = \json_encode($expected);
        $actual = StringUtils::decodeJson($encoded); // @phpstan-ignore argument.type
        self::assertSame($expected, $actual);
    }

    public function testDecodeJsonObject(): void
    {
        $expected = new \stdClass();
        $expected->key = 'value';
        $expected->date = 'date';
        $encoded = \json_encode($expected);
        $actual = StringUtils::decodeJson($encoded, false); // @phpstan-ignore argument.type
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
    }

    public function testEncodeJson(): void
    {
        $expected = '{"key":"value"}';
        /** @psalm-var mixed $decoded */
        $decoded = \json_decode($expected, true);
        $actual = StringUtils::encodeJson($decoded);
        self::assertSame($expected, $actual);
    }

    public function testEncodeJsonWidthException(): void
    {
        self::expectException(\InvalidArgumentException::class);
        $input = \mb_convert_encoding('ø, æ, å', 'ISO-8859-1');
        StringUtils::encodeJson($input);
    }

    #[DataProvider('getEqualIgnoreCase')]
    public function testEqualIgnoreCase(string $string1, string $string2, bool $expected = true): void
    {
        $actual = StringUtils::equalIgnoreCase($string1, $string2);
        self::assertSame($actual, $expected);
    }

    #[DataProvider('getExportVar')]
    public function testExportVar(mixed $var, mixed $expected): void
    {
        $actual = StringUtils::exportVar($var);
        self::assertSame($expected, $actual);
    }

    /**
     * @psalm-param object|class-string|null $var
     *
     * @psalm-suppress PossiblyNullArgument
     */
    #[DataProvider('getShortName')]
    public function testGetShortName(object|string|null $var, mixed $expected, bool $exception = false): void
    {
        if (null === $var) {
            $this->expectException(\TypeError::class);
        } elseif ($exception) {
            $this->expectException(\RuntimeException::class);
        }
        // @phpstan-ignore argument.type
        $actual = StringUtils::getShortName($var);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getIsString')]
    public function testIsString(?string $var, bool $expected): void
    {
        $actual = StringUtils::isString($var);
        self::assertSame($expected, $actual);
    }

    public function testNewLine(): void
    {
        self::assertSame("\n", StringUtils::NEW_LINE);
    }

    /**
     * @psalm-param non-empty-string $pattern
     */
    #[DataProvider('getPregMatch')]
    public function testPregMatch(string $pattern, string $subject, bool $expected): void
    {
        $actual = StringUtils::pregMatch($pattern, $subject);
        self::assertSame($expected, $actual);
    }

    /**
     * @psalm-param non-empty-string $pattern
     */
    #[DataProvider('getPregMatchAll')]
    public function testPregMatchAll(string $pattern, string $subject, bool $expected): void
    {
        $actual = StringUtils::pregMatchAll($pattern, $subject);
        self::assertSame($expected, $actual);
    }

    /**
     * @psalm-param non-empty-string $pattern
     */
    #[DataProvider('getPregReplace')]
    public function testPregReplace(string $pattern, string $replacement, string $subject, string $expected): void
    {
        $actual = StringUtils::pregReplace($pattern, $replacement, $subject);
        self::assertSame($expected, $actual);
    }

    /**
     * @psalm-param non-empty-array<non-empty-string, string> $values
     */
    #[DataProvider('getPregReplaceAll')]
    public function testPregReplaceAll(array $values, string $subject, string $expected): void
    {
        $actual = StringUtils::pregReplaceAll($values, $subject);
        self::assertSame($expected, $actual);
    }

    public function testSlug(): void
    {
        $actual = StringUtils::slug('Wôrķšƥáçè ~~sèťtïñğš~~');
        self::assertSame('Workspace-settings', $actual);
    }

    #[DataProvider('getStartWith')]
    public function testStartWith(string $haystack, string $needle, bool $ignore_case, bool $expected): void
    {
        $actual = StringUtils::startWith($haystack, $needle, $ignore_case);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getTrim')]
    public function testTrim(?string $value, ?string $expected): void
    {
        $actual = StringUtils::trim($value);
        self::assertSame($expected, $actual);
    }

    public function testUnicode(): void
    {
        $actual = StringUtils::unicode('fake')->toString();
        self::assertSame('fake', $actual);

        $actual = StringUtils::unicode('fake', true)->toString();
        self::assertSame('fake', $actual);
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
