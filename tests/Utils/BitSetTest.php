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

use App\Util\BitSet;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for the {@link BitSet} class.
 */
class BitSetTest extends TestCase
{
    public function testBinary(): void
    {
        $bs = new BitSet();
        $bs->sets([0, 1, 2, 12, 58, 65]);
        $binary = $bs->toBinary();
        $result = BitSet::fromBinary($binary);
        self::assertTrue($bs->isEqual($result));
    }

    public function testBinary2(): void
    {
        $bs = new BitSet();
        $bs->sets([0, 1, 2, 22, 43, 65]);
        $binary = $bs->toBinary2();
        $result = BitSet::fromBinary2($binary);
        self::assertTrue($bs->isEqual($result));
    }

    public function testClear(): void
    {
        $bs = new BitSet();
        $bs->set(2);
        self::assertTrue($bs->get(2));
        $bs->clear(2);
        self::assertFalse($bs->get(2));
        self::assertEquals([0], $bs->toArray());
    }

    public function testClearInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $bs = new BitSet();
        $bs->clear(-1);
    }

    public function testClearRange(): void
    {
        $fromIndex = 10;
        $toIndex = 20;

        $bs = new BitSet();
        $bs->clearRange($fromIndex, $toIndex);
        for ($i = $fromIndex; $i < $toIndex; ++$i) {
            self::assertFalse($bs->get($i));
        }
    }

    public function testClears(): void
    {
        $bs = new BitSet();
        $bs->sets([0, 1, 2]);
        $bs->clears([0, 1, 2]);
        self::assertEquals([0], $bs->toArray());
    }

    public function testClearsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $bs = BitSet::fromArray([0, 1, 2]);
        $bs->clears([0, 1, -1]);
    }

    public function testEmpty(): void
    {
        $bs = new BitSet();
        self::assertTrue($bs->isEmpty());
        $bs->set(1);
        self::assertFalse($bs->isEmpty());
    }

    public function testFlip(): void
    {
        $bs = new BitSet();
        $bs->set(1);
        self::assertTrue($bs->get(1));
        $bs->flip(1);
        self::assertFalse($bs->get(1));
        $bs->flip(1);
        self::assertTrue($bs->get(1));
    }

    public function testFlipRange(): void
    {
        $fromIndex = 10;
        $toIndex = 20;

        $bs = new BitSet();
        $bs->flipRange($fromIndex, $toIndex);

        for ($i = $fromIndex; $i < $toIndex; ++$i) {
            self::assertTrue($bs->get($i));
        }
    }

    public function testFromArray(): void
    {
        $words = [0, 1, 2];
        $bs = BitSet::fromArray($words);
        self::assertEquals($words, $bs->toArray());
    }

    public function testGet(): void
    {
        $values = [0, 1, 58];
        $bs = new BitSet();
        foreach ($values as $value) {
            $bs->set($value);
        }
        foreach ($values as $value) {
            self::assertTrue($bs->get($value));
        }
    }

    public function testIndexes(): void
    {
        // 10010100
        $values = [0, 1, 58];
        $bs = new BitSet();
        $bs->sets($values);
        $indexes = $bs->toIndexes();
        self::assertEquals($values, $indexes);
    }

    public function testIsEqual(): void
    {
        $bs = new BitSet();
        $bs->sets([4, 63]);
        $other = new BitSet();
        $other->sets([4, 63]);
        self::assertTrue($bs->isEqual($other));
    }

    public function testIsNotEqual(): void
    {
        $bs = new BitSet();
        $bs->sets([4, 6]);
        $other = new BitSet();
        $other->sets([4, 5]);
        self::assertFalse($bs->isEqual($other));
    }

    public function testLogicalAnd(): void
    {
        $bs = new BitSet();
        $bs->sets([0, 1, 2, 3, 4, 5]);
        $other = new BitSet();
        $other->sets([2, 4, 6, 8, 10]);
        $bs->and($other);

        self::assertFalse($bs->get(0));
        self::assertFalse($bs->get(1));
        self::assertTrue($bs->get(2));
        self::assertFalse($bs->get(3));
        self::assertTrue($bs->get(4));
        self::assertFalse($bs->get(5));
        self::assertFalse($bs->get(6));
        self::assertFalse($bs->get(7));
        self::assertFalse($bs->get(8));
        self::assertFalse($bs->get(9));
        self::assertFalse($bs->get(10));
    }

    public function testLogicalAndNot(): void
    {
        $bs = new BitSet();
        $bs->sets([0, 1, 2, 3, 4, 5]);
        $other = new BitSet();
        $other->sets([2, 4, 6, 8, 10]);
        $bs->andNot($other);

        self::assertTrue($bs->get(0));
        self::assertTrue($bs->get(1));
        self::assertFalse($bs->get(2));
        self::assertTrue($bs->get(3));
        self::assertFalse($bs->get(4));
        self::assertTrue($bs->get(5));
        self::assertFalse($bs->get(6));
        self::assertFalse($bs->get(7));
        self::assertFalse($bs->get(8));
        self::assertFalse($bs->get(9));
        self::assertFalse($bs->get(10));
    }

    public function testLogicalOr(): void
    {
        $bs = new BitSet();
        $bs->sets([0, 1, 2, 3, 4, 5]);
        $other = new BitSet();
        $other->sets([2, 4, 6, 8, 10]);
        $bs->or($other);

        self::assertTrue($bs->get(0));
        self::assertTrue($bs->get(1));
        self::assertTrue($bs->get(2));
        self::assertTrue($bs->get(3));
        self::assertTrue($bs->get(4));
        self::assertTrue($bs->get(5));
        self::assertTrue($bs->get(6));
        self::assertFalse($bs->get(7));
        self::assertTrue($bs->get(8));
        self::assertFalse($bs->get(9));
        self::assertTrue($bs->get(10));
        self::assertFalse($bs->get(11));
    }

    public function testLogicalXor(): void
    {
        $bs = new BitSet();
        $bs->sets([0, 1, 2, 3, 4, 5]);
        $other = new BitSet();
        $other->sets([2, 4, 6, 8, 10]);
        $bs->xor($other);

        self::assertTrue($bs->get(0));
        self::assertTrue($bs->get(1));
        self::assertFalse($bs->get(2));
        self::assertTrue($bs->get(3));
        self::assertFalse($bs->get(4));
        self::assertTrue($bs->get(5));
        self::assertTrue($bs->get(6));
        self::assertFalse($bs->get(7));
        self::assertTrue($bs->get(8));
        self::assertFalse($bs->get(9));
        self::assertTrue($bs->get(10));
        self::assertFalse($bs->get(11));
    }

    public function testReset(): void
    {
        $bs = new BitSet();
        $bs->set(1);
        $bs->reset();
        self::assertTrue($bs->isEmpty());
    }

    public function testSet(): void
    {
        $array = [
            0 => 1,
            1 => 2,
            2 => 4,
        ];
        $bs = new BitSet();
        foreach (\array_keys($array) as $key) {
            $bs->set($key);
        }
        $expected = \array_sum($array);
        self::assertEquals([$expected], $bs->toArray());
    }

    public function testSetInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $bs = new BitSet();
        $bs->set(-1);
    }

    public function testSetRange(): void
    {
        $fromIndex = 10;
        $toIndex = 20;

        $bs = new BitSet();
        $bs->setRange($fromIndex, $toIndex);

        for ($i = $fromIndex; $i < $toIndex; ++$i) {
            self::assertTrue($bs->get($i));
        }
    }

    public function testSetsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $bs = BitSet::fromArray([0, 1, 2]);
        $bs->sets([0, 1, -1]);
    }

    public function testSize(): void
    {
        $value = 4;
        $bs = new BitSet();
        self::assertEquals(0, $bs->size());
        $bs->set($value);
        self::assertEquals($value + 1, $bs->size());
    }

    public function testToString(): void
    {
        $bs = new BitSet();
        $bs->sets([1, 5]);
        $result = (string) $bs;
        self::assertEquals('BitSet{1,5}', $result);
    }

    public function testTrim(): void
    {
        $bs = new BitSet();
        $bs->sets([4, 63]);
        $bs->clear(63);
        $bs->trim();

        self::assertFalse($bs->get(63));
        self::assertTrue($bs->get(4));
        self::assertEquals(5, $bs->size());
    }
}
