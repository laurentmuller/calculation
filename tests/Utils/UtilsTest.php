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
use App\Util\Utils;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for {@link Utils} class.
 */
class UtilsTest extends TestCase
{
    public function getAscii(): array
    {
        return [
            ['home', 'home'],
            ['नमस्ते', 'namaste'],
            ['さよなら', 'sayonara'],
            ['спасибо', 'spasibo'],
        ];
    }

    public function getCapitalize(): array
    {
        return [
            ['home', 'Home'],
            ['hOmE', 'Home'],
            ['my home', 'My home'],
            ['my Home', 'My home'],
            ['my HOME', 'My home'],
        ];
    }

    public function getCompare(): array
    {
        return [
            // equal
            [$this->createData(0, 'd'), $this->createData(0, 'd'), 'value', 0],
            [$this->createData(0, 'd'), $this->createData(0, 'd'), 'string', 0],

            // equal reverse
            [$this->createData(0, 'd'), $this->createData(0, 'd'), 'value', 0, false],
            [$this->createData(0, 'd'), $this->createData(0, 'd'), 'string', 0, false],

            // smaller
            [$this->createData(0, 'd'), $this->createData(10, 'z'), 'value', -1],
            [$this->createData(0, 'd'), $this->createData(10, 'z'), 'string', -1],

            // smaller reverse
            [$this->createData(0, 'd'), $this->createData(10, 'z'), 'value', 1, false],
            [$this->createData(0, 'd'), $this->createData(10, 'z'), 'string', 1, false],

            // greater
            [$this->createData(0, 'd'), $this->createData(-10, 'a'), 'value', 1],
            [$this->createData(0, 'd'), $this->createData(-10, 'a'), 'string', 1],

            // greater reverse
            [$this->createData(0, 'd'), $this->createData(-10, 'a'), 'value', -1, false],
            [$this->createData(0, 'd'), $this->createData(-10, 'a'), 'string', -1, false],

            // ignore case
            [$this->createData(0, 'fake'), $this->createData(0, 'FAKE'), 'string', 0],
            [$this->createData(0, 'FAKE'), $this->createData(0, 'fake'), 'string', 0],
        ];
    }

    public function getContains(): array
    {
        return [
            ['fake', '', false, false],
            ['before ab after', 'ab', false, true],
            ['before AB after', 'ab', false, false],
            ['before AB after', 'ab', true, true],
        ];
    }

    public function getEndWith(): array
    {
        return [
            ['fake', '', false, false],
            ['fake', 'ke', false, true],
            ['fake', 'KE', false, false],
            ['fake', 'KE', true, true],
        ];
    }

    public function getExportVar(): array
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
            [['key' => 'value'], $this->getVarArray()],
        ];
    }

    public function getIsString(): array
    {
        return [
            [null, false],
            ['', false],
            ['my home', true],
        ];
    }

    public function getShortName(): array
    {
        return [
            [null, null, true],
            [self::class, 'UtilsTest'],
            [Calculation::class, 'Calculation'],
            [new Calculation(), 'Calculation'],
            ['invalid argument', null, true],
        ];
    }

    public function getStartWith(): array
    {
        return [
            ['fake', '', false, false],
            ['fake', 'fa', false, true],
            ['fake', 'FA', false, false],
            ['fake', 'FA', true, true],
        ];
    }

    public function getToFloat(): array
    {
        return [
            [null, 0.0, true],
            [0, 0.0, true],
            [1.0, 0, false],
            ['a', 1, false],
            [1, 1.0, true],
        ];
    }

    public function getToInt(): array
    {
        return [
            [null, 0, true],
            [0, 0, true],
            [1.0, 0, false],
            ['a', 1, false],
            [1.0, 1, true],
        ];
    }

    public function getToString(): array
    {
        return [
            [null, '', true],
            [0, '0', true],
            [1.0, '0', false],
            ['a', '1', false],
        ];
    }

    public function getTranslateLevels(): array
    {
        return [
            [-2, 'none'],
            [-1, 'none'],
            [0, 'very_weak'],
            [1, 'weak'],
            [2, 'medium'],
            [3, 'strong'],
            [4, 'very_strong'],
            [5, 'very_strong'],
        ];
    }

    /**
     * @dataProvider getAscii
     */
    public function testAscii(string $value, string $expected): void
    {
        $result = Utils::ascii($value);
        self::assertEquals($expected, $result);
    }

    /**
     * @dataProvider getCapitalize
     */
    public function testCapitalize(string $value, string $expected): void
    {
        $actual = Utils::capitalize($value);
        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider getCompare
     */
    public function testCompare(\stdClass $a, \stdClass $b, string $field, int $expected, bool $ascending = true): void
    {
        $accessor = Utils::getAccessor();
        $actual = Utils::compare($a, $b, $field, $accessor, $ascending);
        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider getContains
     */
    public function testContains(string $haystack, string $needle, bool $ignore_case, bool $expected): void
    {
        $actual = Utils::contains($haystack, $needle, $ignore_case);
        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider getEndWith
     */
    public function testEndWith(string $haystack, string $needle, bool $ignore_case, bool $expected): void
    {
        $actual = Utils::endWith($haystack, $needle, $ignore_case);
        self::assertEquals($expected, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testExceptionContext(): void
    {
        $code = 200;
        $message = 'My message';
        $e = new \Exception($message, $code);
        $result = Utils::getExceptionContext($e);

        self::assertArrayHasKey('message', $result);
        self::assertArrayHasKey('code', $result);
        self::assertArrayHasKey('file', $result);
        self::assertArrayHasKey('line', $result);
        self::assertArrayHasKey('trace', $result);

        self::assertEquals($message, $result['message']);
        self::assertEquals($code, $result['code']);
        self::assertEquals(__FILE__, $result['file']);
    }

    /**
     * @dataProvider getExportVar
     */
    public function testExportVar(mixed $var, mixed $expected): void
    {
        $actual = Utils::exportVar($var);
        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider getShortName
     *
     * @param mixed $var
     */
    public function testGetShortName($var, mixed $expected, bool $exception = false): void
    {
        if (null === $var) {
            $this->expectException(\TypeError::class);
        } elseif ($exception) {
            $this->expectException(\ReflectionException::class);
        }
        $actual = Utils::getShortName($var);
        self::assertEquals($expected, $actual);
    }

    public function testGroupByArrays(): void
    {
        /**
         * @var array<array{id: int, value: string}> $array
         */
        $array = [
            ['id' => 1, 'value' => '1'],
            ['id' => 2, 'value' => '2'],
            ['id' => 2, 'value' => '3'],
        ];
        $key = 'id';

        /**
         * @var array<int, array> $result
         */
        $result = Utils::groupBy($array, $key);

        self::assertArrayHasKey(1, $result);
        self::assertArrayHasKey(2, $result);
        self::assertCount(1, $result[1]);
        self::assertCount(2, $result[2]);
    }

    public function testGroupByCallable(): void
    {
        /**
         * @var array<array{id: int, value: string}> $array
         */
        $array = [
            ['id' => 1, 'value' => '1'],
            ['id' => 2, 'value' => '2'],
            ['id' => 2, 'value' => '3'],
        ];
        $key = fn (array $value): int => (int) $value['id'];

        /**
         * @var array<int, array> $result
         */
        $result = Utils::groupBy($array, $key);

        self::assertArrayHasKey(1, $result);
        self::assertArrayHasKey(2, $result);
        self::assertCount(1, $result[1]);
        self::assertCount(2, $result[2]);
    }

    public function testGroupByMultiple(): void
    {
        /**
         * @var array<array{id0: int, id1: string, value: string}> $array
         */
        $array = [
            ['id0' => 1, 'id1' => '1', 'value' => '1'],
            ['id0' => 1, 'id1' => '1', 'value' => '2'],
            ['id0' => 1, 'id1' => '2', 'value' => '2'],

            ['id0' => 2, 'id1' => '1', 'value' => '2'],
            ['id0' => 2, 'id1' => '1', 'value' => '2'],
            ['id0' => 2, 'id1' => '2', 'value' => '2'],
        ];

        /**
         * @var array<int, array<int, array>> $result
         */
        $result = Utils::groupBy($array, 'id0', 'id1');

        // first level
        self::assertArrayHasKey(1, $result);
        self::assertArrayHasKey(2, $result);
        self::assertCount(2, $result[1]);
        self::assertCount(2, $result[2]);

        // second level - first
        $result1 = $result[1];
        self::assertArrayHasKey(1, $result1);
        self::assertArrayHasKey(2, $result1);
        self::assertCount(2, $result1[1]);
        self::assertCount(1, $result1[2]);

        // second level - second
        $result2 = $result[2];
        self::assertArrayHasKey(1, $result2);
        self::assertArrayHasKey(2, $result2);
        self::assertCount(2, $result2[1]);
        self::assertCount(1, $result2[2]);
    }

    public function testGroupByObjects(): void
    {
        $array = [
            $this->createData(1, '1'),
            $this->createData(2, '2'),
            $this->createData(2, '3'),
        ];
        $key = 'value';
        /**
         * @var array<int, array> $result
         */
        $result = Utils::groupBy($array, $key);

        self::assertArrayHasKey(1, $result);
        self::assertArrayHasKey(2, $result);
        self::assertCount(1, $result[1]);
        self::assertCount(2, $result[2]);
    }

    /**
     * @dataProvider getIsString
     */
    public function testIsString(?string $var, bool $expected): void
    {
        $actual = Utils::isString($var);
        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider getStartWith
     */
    public function testStartWith(string $haystack, string $needle, bool $ignore_case, bool $expected): void
    {
        $actual = Utils::startWith($haystack, $needle, $ignore_case);
        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider getToFloat
     */
    public function testToFloat(mixed $var, float $expected, bool $equal): void
    {
        $actual = Utils::toFloat($var);
        if ($equal) {
            self::assertEquals($actual, $expected);
        } else {
            self::assertNotEquals($actual, $expected);
        }
    }

    /**
     * @dataProvider getToInt
     */
    public function testToInt(mixed $var, int $expected, bool $equal): void
    {
        $actual = Utils::toInt($var);
        if ($equal) {
            self::assertEquals($actual, $expected);
        } else {
            self::assertNotEquals($actual, $expected);
        }
    }

    /**
     * @dataProvider getToString
     */
    public function testToString(mixed $var, string $expected, bool $equal): void
    {
        $actual = Utils::toString($var);
        if ($equal) {
            self::assertEquals($actual, $expected);
        } else {
            self::assertNotEquals($actual, $expected);
        }
    }

    private function createData(int $value, string $string): \stdClass
    {
        return (object) [
            'value' => $value,
            'string' => $string,
        ];
    }

    private function getVarArray(): string
    {
        return <<<ARRAY
            [
              'key' => 'value'
            ]
            ARRAY;
    }
}
