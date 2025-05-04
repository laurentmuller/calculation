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
use App\Tests\PrivateInstanceTrait;
use App\Utils\StringUtils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StringUtilsTest extends TestCase
{
    use PrivateInstanceTrait;

    /**
     * @phpstan-return \Generator<int, array{string, string}>
     */
    public static function getAscii(): \Generator
    {
        yield ['home', 'home'];
        yield ['नमस्ते', 'namaste'];
        yield ['さよなら', 'sayonara'];
        yield ['спасибо', 'spasibo'];
    }

    /**
     * @phpstan-return \Generator<int, array{string, string}>
     */
    public static function getCapitalize(): \Generator
    {
        yield ['home', 'Home'];
        yield ['hOmE', 'Home'];
        yield ['my home', 'My home'];
        yield ['my Home', 'My home'];
        yield ['my HOME', 'My home'];
    }

    /**
     * @phpstan-return \Generator<int, array{0: string, 1: string, 2?: false}>
     */
    public static function getEqualIgnoreCase(): \Generator
    {
        yield ['home', 'Home'];
        yield ['home', 'HOME'];
        yield ['a', 'b', false];
    }

    /**
     * @phpstan-return \Generator<int, array{mixed, mixed}>
     */
    public static function getExportVar(): \Generator
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

    /**
     * @phpstan-return \Generator<int, array{?string, bool}>
     */
    public static function getIsString(): \Generator
    {
        yield [null, false];
        yield ['', false];
        yield ['my home', true];
    }

    /**
     * @phpstan-return \Generator<int, array{non-empty-string, string, bool}>
     */
    public static function getPregMatch(): \Generator
    {
        yield ['/\d+/', '1234', true];
        yield ['/\d+/', 'FAKE', false];
        yield ['/(?J)(?<match>foo)|(?<match>bar)/', 'foo bar', true];
        yield ['/(foo)(bar)(baz)/', 'foobarbaz', true];
        yield ['/(foo)(bar)(baz)/', 'foobaz', false];
    }

    /**
     * @phpstan-return \Generator<int, array{non-empty-string, string, bool}>
     */
    public static function getPregMatchAll(): \Generator
    {
        yield ['/\d+/', '1234', true];
        yield ['/\d+/', 'FAKE', false];

        yield ['/(?J)(?<match>foo)|(?<match>bar)/', 'foo bar', true];
        yield ['/(foo)(bar)(baz)/', 'foobarbaz', true];
        yield ['/(foo)(bar)(baz)/', 'foobaz', false];
    }

    /**
     * @phpstan-return \Generator<int, array{non-empty-string, string, string, string}>
     */
    public static function getPregReplace(): \Generator
    {
        yield ['/\d+/', '', '1234', ''];
        yield ['/\d+/', '', 'FAKE', 'FAKE'];
    }

    /**
     * @phpstan-return \Generator<int, array{non-empty-array<non-empty-string, string>, string, string}>
     */
    public static function getPregReplaceAll(): \Generator
    {
        yield [['/\d+/' => ''], '1234', ''];
        yield [['/\d+/' => ''], 'FAKE', 'FAKE'];
    }

    /**
     * @phpstan-return \Generator<int, array{object|class-string, string}>
     */
    public static function getShortNameValid(): \Generator
    {
        yield [self::class, 'StringUtilsTest'];
        yield [Calculation::class, 'Calculation'];
        yield [new Calculation(), 'Calculation'];
    }

    /**
     * @phpstan-return \Generator<int, array{string, string, bool, bool}>
     */
    public static function getStartWith(): \Generator
    {
        yield ['fake', '', false, false];
        yield ['fake', 'fa', false, true];
        yield ['fake', 'FA', false, false];
        yield ['fake', 'FA', true, true];
    }

    /**
     * @phpstan-return \Generator<int, array{string, ?string}>
     */
    public static function getTrim(): \Generator
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
        /** @phpstan-var non-empty-string $encoded */
        $encoded = \json_encode($expected);
        $actual = StringUtils::decodeJson($encoded);
        self::assertSame($expected, $actual);
    }

    public function testDecodeJsonObject(): void
    {
        $expected = new \stdClass();
        $expected->key = 'value';
        $expected->date = 'date';
        /** @phpstan-var non-empty-string $encoded */
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
    }

    public function testEncodeJson(): void
    {
        $expected = '{"key":"value"}';
        /** @phpstan-var mixed $decoded */
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

    public function testGetShortNameInvalid(): void
    {
        /**
         * @phpstan-var class-string $objectOrClass
         *
         * @phpstan-ignore varTag.nativeType
         */
        $objectOrClass = 'Fake Class';
        $this->expectException(\RuntimeException::class);
        StringUtils::getShortName($objectOrClass);
    }

    /**
     * @phpstan-param object|class-string $objectOrClass
     */
    #[DataProvider('getShortNameValid')]
    public function testGetShortNameValid(object|string $objectOrClass, string $expected): void
    {
        $actual = StringUtils::getShortName($objectOrClass);
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
     * @phpstan-param non-empty-string $pattern
     */
    #[DataProvider('getPregMatch')]
    public function testPregMatch(string $pattern, string $subject, bool $expected): void
    {
        $actual = StringUtils::pregMatch($pattern, $subject);
        self::assertSame($expected, $actual);
    }

    /**
     * @phpstan-param non-empty-string $pattern
     */
    #[DataProvider('getPregMatchAll')]
    public function testPregMatchAll(string $pattern, string $subject, bool $expected): void
    {
        $actual = StringUtils::pregMatchAll($pattern, $subject);
        self::assertSame($expected, $actual);
    }

    /**
     * @phpstan-param non-empty-string $pattern
     */
    #[DataProvider('getPregReplace')]
    public function testPregReplace(string $pattern, string $replacement, string $subject, string $expected): void
    {
        $actual = StringUtils::pregReplace($pattern, $replacement, $subject);
        self::assertSame($expected, $actual);
    }

    /**
     * @phpstan-param non-empty-array<non-empty-string, string> $values
     */
    #[DataProvider('getPregReplaceAll')]
    public function testPregReplaceAll(array $values, string $subject, string $expected): void
    {
        $actual = StringUtils::pregReplaceAll($values, $subject);
        self::assertSame($expected, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testPrivateInstance(): void
    {
        self::assertPrivateInstance(StringUtils::class);
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
