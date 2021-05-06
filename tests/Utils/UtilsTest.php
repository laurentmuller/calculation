<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
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
 * Unit test for Utils.
 *
 * @author Laurent Muller
 *
 * @see Utils
 */
class UtilsTest extends TestCase
{
    public function getArrayValue(): array
    {
        return [
            [[], 'fake', null],
            [[], null, null],
            [['a'], 'fake', null],
            [['a'], null, null],
            [['k' => 'a'], 'k', 'a'],
            [['k' => 'a'], 'f', 'b', 'b'],
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

    public function getEndwith(): array
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
            [true, 'true'],
            [false, 'false'],
            [0, '0'],
            [0.0, '0.0'],
            [0.01, '0.01'],
            [1000, '1000'],
            ['fake', "'fake'"],
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
            [null, null],
            [self::class, 'UtilsTest'],
            [Calculation::class, 'Calculation'],
            [new Calculation(), 'Calculation'],
            ['invalid argument', null, true],
        ];
    }

    public function getStartwith(): array
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

    public function testAccessor(): void
    {
        $accessor = Utils::getAccessor();
        $this->assertNotNull($accessor);
    }

    /**
     * @dataProvider getCapitalize
     */
    public function testCapitalize(string $value, string $expected): void
    {
        $actual = Utils::capitalize($value);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider getCompare
     */
    public function testCompare(\stdClass $a, \stdClass $b, string $field, int $expected, bool $ascending = true): void
    {
        $accessor = Utils::getAccessor();
        $actual = Utils::compare($a, $b, $field, $accessor, $ascending);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider getContains
     */
    public function testContains(string $haystack, string $needle, bool $ignorecase, bool $expected): void
    {
        $actual = Utils::contains($haystack, $needle, $ignorecase);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider getEndwith
     */
    public function testEndwith(string $haystack, string $needle, bool $ignorecase, bool $expected): void
    {
        $actual = Utils::endwith($haystack, $needle, $ignorecase);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider getExportVar
     *
     * @param mixed $var
     * @param mixed $expected
     */
    public function testExportVar($var, $expected): void
    {
        $actual = Utils::exportVar($var);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider getArrayValue
     * @param mixed $key
     * @param mixed $expected
     * @param mixed $default
     */
    public function testGetArrayValue(array $array, $key, $expected, $default = null): void
    {
        $actual = Utils::getArrayValue($array, $key, $default);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider getShortName
     *
     * @param mixed $var
     * @param mixed $expected
     */
    public function testGetShortName($var, $expected, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(\ReflectionException::class);
        }
        $actual = Utils::getShortName($var);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider getIsString
     */
    public function testIsString(?string $var, bool $expected): void
    {
        $actual = Utils::isString($var);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider getStartwith
     */
    public function testStartwith(string $haystack, string $needle, bool $ignorecase, bool $expected): void
    {
        $actual = Utils::startwith($haystack, $needle, $ignorecase);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider getToFloat
     *
     * @param mixed $var
     */
    public function testToFloat($var, float $expected, bool $equal): void
    {
        $actual = Utils::toFloat($var);
        if ($equal) {
            $this->assertEquals($actual, $expected);
        } else {
            $this->assertNotEquals($actual, $expected);
        }
    }

    /**
     * @dataProvider getToInt
     *
     * @param mixed $var
     */
    public function testToInt($var, int $expected, bool $equal): void
    {
        $actual = Utils::toInt($var);
        if ($equal) {
            $this->assertEquals($actual, $expected);
        } else {
            $this->assertNotEquals($actual, $expected);
        }
    }

    /**
     * @dataProvider getToString
     *
     * @param mixed $var
     */
    public function testToString($var, string $expected, bool $equal): void
    {
        $actual = Utils::toString($var);
        if ($equal) {
            $this->assertEquals($actual, $expected);
        } else {
            $this->assertNotEquals($actual, $expected);
        }
    }

    private function createData(int $value, string $string): \stdClass
    {
        return (object) [
            'value' => $value,
            'string' => $string,
        ];
    }
}
