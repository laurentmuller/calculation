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

use App\Traits\ArrayTrait;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(ArrayTrait::class)]
class ArrayTraitTest extends TestCase
{
    use ArrayTrait;

    private const KEY = 'key';

    public static function getColumns(): \Generator
    {
        $values = [
            [self::KEY => 1.0],
            [self::KEY => 2.0],
        ];
        $expected = [1.0, 2.0];
        yield [$values, $expected];

        $values = [
            [self::KEY => 1.0],
            [self::KEY => 2.0],
            [self::KEY => null],
        ];
        $expected = [1.0, 2.0, null];
        yield [$values, $expected];
    }

    public static function getFilterValues(): \Generator
    {
        // empty
        $values = [
            [self::KEY => null],
            [self::KEY => false],
            [self::KEY => ''],
            [self::KEY => 0],
            [self::KEY => '0'],
            [self::KEY => 0.0],
        ];
        $expected = [];
        yield [$values, $expected];

        // default
        $values = [
            [self::KEY => 1.0],
            [self::KEY => 2.0],
        ];
        $expected = [1.0, 2.0];
        yield [$values, $expected];

        // callback
        $values = [
            [self::KEY => 1.0],
            [self::KEY => 2.0],
            [self::KEY => 3.0],
            [self::KEY => 4.0],
        ];
        $callback = fn (float $value): bool => $value < 4.0;
        $expected = [1.0, 2.0, 3.0];
        yield [$values, $expected, $callback];

        $callback = fn (float $value): bool => $value >= 4.0;
        $expected = [3 => 4.0];
        yield [$values, $expected, $callback];
    }

    public static function getMaxValues(): \Generator
    {
        // empty
        yield [[], -1.0, -1.0];
        yield [[], 0.0];
        yield [[], 1.0, 1.0];

        $values = [
            [self::KEY => 0.0],
            [self::KEY => 1.0],
        ];
        yield [$values, 1.0];

        $values = [
            [self::KEY => -1.0],
            [self::KEY => 0.0],
            [self::KEY => 1.0],
        ];
        yield [$values, 1.0];

        $values = [
            [self::KEY => 1.0],
            [self::KEY => 2.0],
        ];
        yield [$values, 2.0];
    }

    public static function getSumValues(): \Generator
    {
        // empty
        yield [[], -1.0, -1.0];
        yield [[], 0.0];
        yield [[], 1.0, 1.0];

        $values = [
            [self::KEY => 0.0],
            [self::KEY => 1.0],
        ];
        yield [$values, 1.0];

        $values = [
            [self::KEY => -1.0],
            [self::KEY => 0.0],
            [self::KEY => 1.0],
        ];
        yield [$values, 0.0];

        $values = [
            [self::KEY => 1.0],
            [self::KEY => 2.0],
        ];
        yield [$values, 3.0];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getColumns')]
    public function testColumn(array $values, array $expected): void
    {
        $actual = $this->getColumn($values, self::KEY);
        self::assertSame($expected, $actual);
    }

    /**
     * @psalm-param int<0,2> $mode
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getFilterValues')]
    public function testColumnFilter(array $values, array $expected, callable $callback = null, int $mode = 0): void
    {
        $actual = $this->getColumnFilter($values, self::KEY, $callback, $mode);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getMaxValues')]
    public function testColumnMax(array $values, float $expected, float $default = 0.0): void
    {
        $actual = $this->getColumnMax($values, self::KEY, $default);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getSumValues')]
    public function testColumnSum(array $values, float $expected, float $default = 0.0): void
    {
        $actual = $this->getColumnSum($values, self::KEY, $default);
        self::assertSame($expected, $actual);
    }
}