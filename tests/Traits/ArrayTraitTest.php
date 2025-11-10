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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ArrayTraitTest extends TestCase
{
    use ArrayTrait;

    private const KEY = 'key';

    /**
     * @phpstan-return \Generator<int, array{0: array, 1: array, 2?: callable}>
     */
    public static function getColumnFilterValues(): \Generator
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
        $callback = static fn (float $value): bool => $value < 4.0;
        $expected = [1.0, 2.0, 3.0];
        yield [$values, $expected, $callback];

        $callback = static fn (float $value): bool => $value >= 4.0;
        $expected = [3 => 4.0];
        yield [$values, $expected, $callback];
    }

    /**
     * @phpstan-return \Generator<int, array{0: array, 1: float, 2?: float}>
     */
    public static function getColumnMaxValues(): \Generator
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

    /**
     * @phpstan-return \Generator<int, array{0: array, 1: float, 2?: float}>
     */
    public static function getColumnSumValues(): \Generator
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

    /**
     * @phpstan-return \Generator<int, array{array, array}>
     */
    public static function getColumnValues(): \Generator
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

    /**
     * @phpstan-return \Generator<int, array{array, array}>
     */
    public static function getUniqueFilteredValues(): \Generator
    {
        $expected = [];
        $values = [];
        yield [$values, $expected];

        $values = [null, '', [], false, 0, '0'];
        yield [$values, $expected];

        $values = ['value', 'value'];
        $expected = ['value'];
        yield [$values, $expected];
    }

    /**
     * @phpstan-return \Generator<int, array{array, array, array}>
     */
    public static function getUniqueMergedValues(): \Generator
    {
        yield [[], [], []];
        yield [[], ['value'], ['value']];
        yield [['value'], [], ['value']];
        yield [['value'], ['value'], ['value']];
        yield [['value', 'value'], ['value'], ['value']];
        yield [['value', 'value'], ['value', 'value'], ['value']];
        yield [['value1'], ['value2'], ['value1', 'value2']];
        yield [['value1', 'value2'], ['value1', 'value2'], ['value1', 'value2']];
        yield [['value', 'value1'], ['value2'], ['value', 'value1', 'value2']];
    }

    public function testAnyMatch(): void
    {
        $closure = static fn (int $value): bool => 2 === $value;

        $array = [];
        $actual = $this->anyMatch($array, $closure);
        self::assertFalse($actual);

        $array = [1, 2, 3, 4];
        $actual = $this->anyMatch($array, $closure);
        self::assertTrue($actual);
    }

    #[DataProvider('getColumnValues')]
    public function testColumn(array $values, array $expected): void
    {
        $actual = $this->getColumn($values, self::KEY);
        self::assertSame($expected, $actual);
    }

    /**
     * @phpstan-param int<0,2> $mode
     */
    #[DataProvider('getColumnFilterValues')]
    public function testColumnFilter(array $values, array $expected, ?callable $callback = null, int $mode = 0): void
    {
        $actual = $this->getColumnFilter($values, self::KEY, $callback, $mode);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getColumnMaxValues')]
    public function testColumnMax(array $values, float $expected, float $default = 0.0): void
    {
        $actual = $this->getColumnMax($values, self::KEY, $default);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getColumnSumValues')]
    public function testColumnSum(array $values, float $expected, float $default = 0.0): void
    {
        $actual = $this->getColumnSum($values, self::KEY, $default);
        self::assertSame($expected, $actual);
    }

    public function testFindFirstWithKey(): void
    {
        $array = [];
        $closure = static fn (string $value, int $key): bool => 2 === $key;
        $actual = $this->findFirst($array, $closure); // @phpstan-ignore argument.type
        self::assertNull($actual);

        $array = $this->createArray();
        $closure = static fn (string $value, int $key): bool => 10 === $key;
        $actual = $this->findFirst($array, $closure); // @phpstan-ignore argument.type
        self::assertSame('B', $actual);

        $closure = static fn (string $value, int $key): bool => 40 === $key;
        $actual = $this->findFirst($array, $closure); // @phpstan-ignore argument.type
        self::assertNull($actual);
    }

    public function testFindFirstWithValue(): void
    {
        $array = [];
        $closure = static fn (string $value): bool => 'A' === $value;
        $actual = $this->findFirst($array, $closure);
        self::assertNull($actual);

        $array = $this->createArray();
        $closure = static fn (string $value): bool => 'B' === $value;
        $actual = $this->findFirst($array, $closure);
        self::assertSame('B', $actual);

        $closure = static fn (string $value): bool => '4' === $value;
        $actual = $this->findFirst($array, $closure);
        self::assertNull($actual);
    }

    public function testMapKeyAndValue(): void
    {
        $actual = $this->mapKeyAndValue(
            $this->createArray(),
            static fn (int $key, string $value): string => \sprintf('%02d: %s', $key, $value)
        );
        $expected = [
            '00: A',
            '10: B',
            '20: C',
        ];
        self::assertSame($expected, $actual);
    }

    public function testMapToKeyValue(): void
    {
        $values = [
            [
                'key' => 'My Key',
                'value' => 'My Value',
            ],
        ];
        $actual = $this->mapToKeyValue(
            $values,
            static fn (array $entry): array => [$entry['key'] => $entry['value']]
        );
        self::assertSame(['My Key' => 'My Value'], $actual);
    }

    public function testRemoveValue(): void
    {
        $values = $this->createArray();
        $actual = $this->removeValue($values, 'FAKE');
        self::assertSame($values, $actual);

        $actual = $this->removeValue($values, 'A');
        self::assertSame([10 => 'B',  20 => 'C'], $actual);
    }

    public function testSorted(): void
    {
        $array = [];
        $this->getSorted($array);
        self::assertSame([], $array);

        $array = ['C', 'A'];
        $this->getSorted($array);
        self::assertSame([0 => 'C', 1 => 'A'], $array);
    }

    /**
     * @phpstan-param 0|1|2 $mode
     */
    #[DataProvider('getUniqueFilteredValues')]
    public function testUniqueFiltered(array $values, array $expected, ?callable $callback = null, int $mode = 0): void
    {
        $actual = $this->getUniqueFiltered($values, $callback, $mode);
        $expectedCount = \count($expected);
        self::assertCount($expectedCount, $actual);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getUniqueMergedValues')]
    public function testUniqueMerged(array $first, array $second, array $expected): void
    {
        $actual = $this->getUniqueMerged($first, $second);
        $expectedCount = \count($expected);
        self::assertCount($expectedCount, $actual);
        self::assertSame($expected, $actual);
    }

    /**
     * @phpstan-return array<int, string>
     */
    private function createArray(): array
    {
        return [0 => 'A', 10 => 'B', 20 => 'C'];
    }
}
