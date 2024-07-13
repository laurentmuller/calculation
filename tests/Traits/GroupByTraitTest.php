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

use App\Traits\GroupByTrait;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-type UtilsType = array{id: int, value: string}
 */
class GroupByTraitTest extends TestCase
{
    use GroupByTrait;

    public function testGroupByArrays(): void
    {
        /** @psalm-var UtilsType[] $array */
        $array = [
            $this->createObject(1, '1'),
            $this->createObject(2, '2'),
            $this->createObject(2, '3'),
        ];
        $result = $this->groupBy($array, 'id');

        self::assertIsArray($result[1]);
        self::assertIsArray($result[2]);
        self::assertCount(1, $result[1]);
        self::assertCount(2, $result[2]);
    }

    public function testGroupByCallable(): void
    {
        /** @psalm-var UtilsType[] $array */
        $array = [
            $this->createObject(1, '1'),
            $this->createObject(2, '2'),
            $this->createObject(2, '3'),
        ];
        $key = fn (array $value): int => (int) $value['id'];
        $result = $this->groupBy($array, $key);

        self::assertIsArray($result[1]);
        self::assertIsArray($result[2]);
        self::assertCount(1, $result[1]);
        self::assertCount(2, $result[2]);
    }

    public function testGroupByEmpty(): void
    {
        /** @psalm-var array<array{key: string, ...}> $array */
        $array = [];
        $actual = $this->groupBy($array, 'key');
        self::assertSame([], $actual);
    }

    public function testGroupByMultiple(): void
    {
        /** @psalm-var array<array{id0: int, id1: string, value: string}> $array */
        $array = [
            ['id0' => 1, 'id1' => '1', 'value' => '1'],
            ['id0' => 1, 'id1' => '1', 'value' => '2'],
            ['id0' => 1, 'id1' => '2', 'value' => '2'],

            ['id0' => 2, 'id1' => '1', 'value' => '2'],
            ['id0' => 2, 'id1' => '1', 'value' => '2'],
            ['id0' => 2, 'id1' => '2', 'value' => '2'],
        ];
        $result = $this->groupBy($array, 'id0', 'id1');

        // first level
        self::assertIsArray($result[1]);
        self::assertIsArray($result[2]);
        self::assertCount(2, $result[1]);
        self::assertCount(2, $result[2]);

        // second level - first
        $result1 = $result[1];
        self::assertIsArray($result1[1]);
        self::assertIsArray($result1[2]);
        self::assertCount(2, $result1[1]);
        self::assertCount(1, $result1[2]);

        // second level - second
        $result2 = $result[2];
        self::assertIsArray($result2[1]);
        self::assertIsArray($result2[2]);
        self::assertCount(2, $result2[1]);
        self::assertCount(1, $result2[2]);
    }

    public function testGroupByObjectInt(): void
    {
        $array = [
            (object) $this->createObject(1, '1'),
            (object) $this->createObject(2, '2'),
            (object) $this->createObject(2, '3'),
        ];
        $key = 'id';
        $result = $this->groupBy($array, $key);

        self::assertArrayHasKey(1, $result);
        self::assertArrayHasKey(2, $result);
        self::assertIsArray($result[1]);
        self::assertIsArray($result[2]);
        self::assertCount(1, $result[1]);
        self::assertCount(2, $result[2]);
    }

    public function testGroupByObjectStrings(): void
    {
        $array = [
            $this->createObject(1, '1'),
            $this->createObject(2, '2'),
            $this->createObject(3, '2'),
        ];
        $key = 'value';
        $result = $this->groupBy($array, $key);

        self::assertArrayHasKey('1', $result);
        self::assertArrayHasKey('2', $result);
        self::assertCount(1, (array) $result['1']);
        self::assertCount(2, (array) $result['2']);
    }

    /**
     * @psalm-return UtilsType
     */
    private function createObject(int $id, string $value): array
    {
        return ['id' => $id, 'value' => $value];
    }
}
