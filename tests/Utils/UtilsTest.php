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

use App\Util\Utils;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for {@link Utils} class.
 */
class UtilsTest extends TestCase
{
    public function testExceptionContext(): void
    {
        $code = 200;
        $message = 'My message';
        $file = __FILE__;
        $line = __LINE__ + 1;
        $e = new \Exception($message, $code);

        $result = Utils::getExceptionContext($e);

        self::assertArrayHasKey('message', $result);
        self::assertArrayHasKey('code', $result);
        self::assertArrayHasKey('file', $result);
        self::assertArrayHasKey('line', $result);
        self::assertArrayHasKey('trace', $result);

        self::assertSame($message, $result['message']);
        self::assertSame($code, $result['code']);
        self::assertSame($file, $result['file']);
        self::assertSame($line, $result['line']);
    }

    public function testGroupByArrays(): void
    {
        /** @psalm-var array<array{id: int, value: string}> $array */
        $array = [
            ['id' => 1, 'value' => '1'],
            ['id' => 2, 'value' => '2'],
            ['id' => 2, 'value' => '3'],
        ];
        $key = 'id';

        /** @psalm-var array<int, array> $result */
        $result = Utils::groupBy($array, $key);

        self::assertArrayHasKey(1, $result);
        self::assertArrayHasKey(2, $result);
        self::assertCount(1, $result[1]);
        self::assertCount(2, $result[2]);
    }

    public function testGroupByCallable(): void
    {
        /** @psalm-var array<array{id: int, value: string}> $array */
        $array = [
            ['id' => 1, 'value' => '1'],
            ['id' => 2, 'value' => '2'],
            ['id' => 2, 'value' => '3'],
        ];

        /** @psalm-var callable(mixed): string $key */
        $key = fn (array $value): int => (int) $value['id'];

        /** @psalm-var array<int, array> $result */
        $result = Utils::groupBy($array, $key);

        self::assertArrayHasKey(1, $result);
        self::assertArrayHasKey(2, $result);
        self::assertCount(1, $result[1]);
        self::assertCount(2, $result[2]);
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

        /** @psalm-var array<int, array<int, array>> $result */
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

    public function testGroupByObjectInt(): void
    {
        $array = [
            $this->createObject(1, '1'),
            $this->createObject(2, '2'),
            $this->createObject(2, '3'),
        ];
        $key = 'value';

        /** @psalm-var array<int, array> $array */
        $result = Utils::groupBy($array, $key);

        self::assertArrayHasKey(1, $result);
        self::assertArrayHasKey(2, $result);
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
        $key = 'string';

        /** @psalm-var array<string, array> $array */
        $result = Utils::groupBy($array, $key);

        self::assertArrayHasKey('1', $result);
        self::assertArrayHasKey('2', $result);
        self::assertCount(1, $result['1']);
        self::assertCount(2, $result['2']);
    }

    private static function createObject(int $value, string $string): object
    {
        return (object) [
            'value' => $value,
            'string' => $string,
        ];
    }
}
