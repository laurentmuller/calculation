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

namespace App\Tests\Traits;

use App\Traits\MathTrait;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for {@link MathTrait} class.
 *
 * @see MathTrait
 */
class MathTraitTest extends TestCase
{
    use MathTrait;

    public static function getIsBitSet(): array
    {
        return [
            [31, 1],
            [31, 2],
            [31, 4],
            [31, 8],
            [31, 16],

            [0, 1, false],
            [0, 2, false],
            [0, 4, false],
            [0, 8, false],
            [0, 16, false],
        ];
    }

    public static function getIsFloatEquals(): array
    {
        return [
            [0, 0],
            [0, 0.01, 0],
            [0, 0.01, 1],

            [1, 0, 2, false],
            [0, 0.01, 2, false],
        ];
    }

    public static function getIsFloatZero(): array
    {
        return [
            [0],
            [0, 0],
            [0, 1],
            [0, 2],
            [0.001, 1],
            [0.001, 2],

            [1, 2, false],
            [0.1, 1, false],
            [0.1, 2, false],
            [0.001, 3, false],
        ];
    }

    public static function getRound(): array
    {
        return [
            [0, 0.0],
            [1.5, 1.5],
            [1.55, 1.55],
            [1.5545, 1.55],
            [1.52, 1.5, 1],
        ];
    }

    public static function getSafeDivide(): array
    {
        return [
            [100, 0,  0.0],
            [100, 5,  20.0],
            [100, 10,  10.0],
            [100, 0,  11.0,  11.0],
        ];
    }

    public static function getValidateFloatRange(): array
    {
        return [
            [0.0,  0, 100.0, 0.0],
            [100.0,  0, 100.0, 100.0],
            [50.0,  0.0, 100.0, 50.0],
            [-0.1,  0.0, 100.0, 0.0],
            [100.1,  0.0, 100.0, 100.0],
        ];
    }

    public static function getValidateIntRange(): array
    {
        return [
            [0,  0, 100, 0],
            [100,  0, 100, 100],
            [50,  0, 100, 50],
            [-1,  0, 100, 0],
            [101,  0, 100, 100],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getIsBitSet')]
    public function testIsBitSet(int $value, int $mask, bool $expected = true): void
    {
        $actual = $this->isBitSet($value, $mask);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getIsFloatEquals')]
    public function testIsFloatEquals(float $val1, float $val2, int $precision = 2, bool $expected = true): void
    {
        $actual = $this->isFloatEquals($val1, $val2, $precision);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getIsFloatZero')]
    public function testIsFloatZero(float $val, int $precision = 2, bool $expected = true): void
    {
        $actual = $this->isFloatZero($val, $precision);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getRound')]
    public function testRound(float $val, float $expected, int $precision = 2): void
    {
        $actual = $this->round($val, $precision);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getSafeDivide')]
    public function testSafeDivide(float $dividend, float $divisor, float $expected, float $default = 0.0): void
    {
        $actual = $this->safeDivide($dividend, $divisor, $default);
        self::assertSame($expected, $actual);
        self::assertSame(11.0, $this->safeDivide(100, 0, 11));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getValidateFloatRange')]
    public function testValidateFloatRange(float $value, float $min, float $max, float $expected): void
    {
        $actual = $this->validateFloatRange($value, $min, $max);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getValidateIntRange')]
    public function testValidateIntRange(int $value, int $min, int $max, int $expected): void
    {
        $actual = $this->validateIntRange($value, $min, $max);
        self::assertSame($expected, $actual);
    }
}
