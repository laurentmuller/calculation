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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MathTraitTest extends TestCase
{
    use MathTrait;

    /**
     * @psalm-return \Generator<array-key, array{float, float}>
     */
    public static function getCeil(): \Generator
    {
        yield [4.3, 4.30];
        yield [9.999, 10.0];
        yield [-3.14, -3.14];
    }

    /**
     * @psalm-return \Generator<array-key, array{float, float}>
     */
    public static function getFloor(): \Generator
    {
        yield [4.3, 4.30];
        yield [9.999, 9.99];
        yield [-3.14, -3.14];
    }

    /**
     * @psalm-return \Generator<array-key, array{0: int, 1: int, 2?: false}>
     */
    public static function getIsBitSet(): \Generator
    {
        yield [31, 1];
        yield [31, 2];
        yield [31, 4];
        yield [31, 8];
        yield [31, 16];
        yield [0, 1, false];
        yield [0, 2, false];
        yield [0, 4, false];
        yield [0, 8, false];
        yield [0, 16, false];
    }

    /**
     * float $val1, float $val2, int $precision = 2, bool $expected = true.
     *
     * @psalm-return \Generator<array-key, array{0: float, 1: float, 2?: int, 3?: bool}>
     */
    public static function getIsFloatEquals(): \Generator
    {
        yield [0, 0];
        yield [0, 0.01, 0];
        yield [0, 0.01, 1];
        yield [1.15, 1.154];
        yield [1.15, 1.155];
        yield [1, 0, 2, false];
        yield [0, 0.01, 2, false];
    }

    /**
     * @psalm-return \Generator<array-key, array{0: float, 1?: int, 2?: false}>
     */
    public static function getIsFloatZero(): \Generator
    {
        yield [0];
        yield [0, 0];
        yield [0, 1];
        yield [0, 2];
        yield [0.001, 1];
        yield [0.001, 2];
        yield [1, 2, false];
        yield [0.1, 1, false];
        yield [0.1, 2, false];
        yield [0.001, 3, false];
    }

    /**
     * float $val, float $expected, int $precision = 2.
     *
     * @psalm-return \Generator<array-key, array{0: float, 1: float, 2?: int}>
     */
    public static function getRound(): \Generator
    {
        yield [0, 0.0];
        yield [1.5, 1.5];
        yield [1.55, 1.55];
        yield [1.5545, 1.55];
        yield [1.52, 1.5, 1];
        yield [1.1549, 1.15];
        yield [1.155, 1.15];
        yield [1.1551, 1.16];
    }

    /**
     * @psalm-return \Generator<array-key, array{0: float, 1: float, 2: float, 3?: 11.0}>
     */
    public static function getSafeDivide(): \Generator
    {
        yield [100, 0,  0.0];
        yield [100, 5,  20.0];
        yield [100, 10,  10.0];
        yield [100, 0,  11.0,  11.0];
    }

    /**
     * @psalm-return \Generator<array-key, array{float, float, float, float}>
     */
    public static function getValidateFloatRange(): \Generator
    {
        yield [0.0,  0, 100.0, 0.0];
        yield [100.0,  0, 100.0, 100.0];
        yield [50.0,  0.0, 100.0, 50.0];
        yield [-0.1,  0.0, 100.0, 0.0];
        yield [100.1,  0.0, 100.0, 100.0];
    }

    /**
     * @psalm-return \Generator<array-key, array{int, int, int, int}>
     */
    public static function getValidateIntRange(): \Generator
    {
        yield [0,  0, 100, 0];
        yield [100,  0, 100, 100];
        yield [50,  0, 100, 50];
        yield [-1,  0, 100, 0];
        yield [101,  0, 100, 100];
    }

    #[DataProvider('getCeil')]
    public function testCeil(float $value, float $expected): void
    {
        $actual = $this->ceil($value);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getFloor')]
    public function testFloor(float $value, float $expected): void
    {
        $actual = $this->floor($value);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getIsBitSet')]
    public function testIsBitSet(int $value, int $mask, bool $expected = true): void
    {
        $actual = $this->isBitSet($value, $mask);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getIsFloatEquals')]
    public function testIsFloatEquals(float $val1, float $val2, int $precision = 2, bool $expected = true): void
    {
        $actual = $this->isFloatEquals($val1, $val2, $precision);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getIsFloatZero')]
    public function testIsFloatZero(float $val, int $precision = 2, bool $expected = true): void
    {
        $actual = $this->isFloatZero($val, $precision);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getRound')]
    public function testRound(float $val, float $expected, int $precision = 2): void
    {
        $actual = $this->round($val, $precision);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getSafeDivide')]
    public function testSafeDivide(float $dividend, float $divisor, float $expected, float $default = 0.0): void
    {
        $actual = $this->safeDivide($dividend, $divisor, $default);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getValidateFloatRange')]
    public function testValidateFloatRange(float $value, float $min, float $max, float $expected): void
    {
        $actual = $this->validateRange($value, $min, $max);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getValidateIntRange')]
    public function testValidateIntRange(int $value, int $min, int $max, int $expected): void
    {
        $actual = $this->validateRange($value, $min, $max);
        self::assertSame($expected, $actual);
    }
}
