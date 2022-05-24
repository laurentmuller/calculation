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

namespace App\Util;

/**
 * Bit manipulation class.
 */
class BitSet
{
    /**
     * The number of bits per word.
     */
    private const WORD_BITS = 31;

    /**
     * @var int[]
     */
    private array $words = [];

    public function __toString(): string
    {
        return \sprintf('BitSet{%s}', \implode(',', $this->toIndexes()));
    }

    /**
     * Perform logical AND.
     */
    public function and(self $other): self
    {
        return $this->applyLogical($other, static fn (int $value, int $other): int => $value & $other);
    }

    /**
     * Perform logical NOT AND.
     */
    public function andNot(self $other): self
    {
        return $this->applyLogical($other, static fn (int $value, int $other): int => $value & ~$other);
    }

    /**
     * Clear the specified bit.
     *
     * @throws \InvalidArgumentException If the bit argument is negative
     */
    public function clear(int $bit): self
    {
        if ($bit < 0) {
            throw new \InvalidArgumentException("The bit argument '$bit' is negative");
        }

        // get word position
        $index = \intdiv($bit, self::WORD_BITS);
        if ($index < $this->count()) {
            $this->words[$index] &= ~$this->bitValue($bit);
        }

        return $this;
    }

    /**
     * Sets the bits from the specified fromIndex (inclusive) to the specified toIndex (exclusive) to false.
     *
     * @param int $fromIndex index of the first bit to be cleared
     * @param int $toIndex   index after the last bit to be cleared
     *
     * @throws \InvalidArgumentException if an index in the given range is negative
     */
    public function clearRange(int $fromIndex, int $toIndex): self
    {
        return $this->applyRange($fromIndex, $toIndex, fn (int $bit): self => $this->clear($bit));
    }

    /**
     * Clear the specified bits.
     *
     * @param int[] $bits the bits to clear
     *
     * @throws \InvalidArgumentException If one of the bit argument is negative
     */
    public function clears(array $bits): self
    {
        foreach ($bits as $bit) {
            $this->clear($bit);
        }

        return $this;
    }

    /**
     * Sets the bit at the specified index to the complement of its current value.
     *
     * @throws \InvalidArgumentException If the bit argument is negative
     */
    public function flip(int $bit): self
    {
        return $this->get($bit) ? $this->clear($bit) : $this->set($bit);
    }

    /**
     * Sets each bit from the specified fromIndex (inclusive) to the specified toIndex (exclusive)
     * to the complement of its current value.
     *
     * @param int $fromIndex index of the first bit to flip
     * @param int $toIndex   index after the last bit to flip
     *
     * @throws \InvalidArgumentException if an index in the given range is negative
     */
    public function flipRange(int $fromIndex, int $toIndex): self
    {
        return $this->applyRange($fromIndex, $toIndex, fn (int $bit): self => $this->flip($bit));
    }

    /**
     * Create an instance from an int array.
     *
     * @param int[] $words
     */
    public static function fromArray(array $words = []): self
    {
        $bs = new self();
        $bs->words = \array_values($words);

        return $bs;
    }

    /**
     * Create an instance from binary data.
     *
     * @see BitSet::toBinary()
     */
    public static function fromBinary(string $bin): self
    {
        /** @psalm-var int[] $words $words */
        $words = (array) \unpack('V*', $bin);

        return static::fromArray($words);
    }

    /**
     * Create an instance from binary data.
     *
     * @see BitSet::toBinary()
     */
    public static function fromBinary2(string $bin): self
    {
        // ensure size
        $size = \PHP_INT_SIZE * 4;
        $remain = $size - \strlen($bin) % $size;
        if ($remain > 0) {
            $bin = \str_repeat('0', $remain) . $bin;
        }

        /** @psalm-var int[] $words $words */
        $words = [];
        $chunks = \str_split($bin, $size);
        foreach ($chunks as $chunk) {
            $words[] = (int) \bindec($chunk);
        }
        $words = \array_reverse($words);

        return self::fromArray($words);
    }

    /**
     * Determine if the specified bit is true.
     */
    public function get(int $bit): bool
    {
        if ($bit < 0 || $this->isEmpty()) {
            return false;
        }

        // get word position
        $index = \intdiv($bit, self::WORD_BITS);

        $size = $this->count();
        if ($size < $index) {
            return false;
        }

        return ($this->words[$index] & $this->bitValue($bit)) !== 0;
    }

    /**
     * Returns if this instance contains no value.
     */
    public function isEmpty(): bool
    {
        return 0 === $this->size();
    }

    /**
     * Return if the given Bitset is equal to this instance.
     */
    public function isEqual(self $other): bool
    {
        return $this === $other || $this->words === $other->words;
    }

    /**
     *  Perform logical OR.
     */
    public function or(self $other): self
    {
        return $this->applyLogical($other, static fn (int $value, int $other): int => $value | $other);
    }

    /**
     * Reset all values.
     */
    public function reset(): self
    {
        $this->words = [];

        return $this;
    }

    /**
     * Set the specified bit to true.
     *
     * @throws \InvalidArgumentException If the bit argument is negative
     */
    public function set(int $bit): self
    {
        if ($bit < 0) {
            throw new \InvalidArgumentException("The bit argument '$bit' is negative");
        }

        $index = \intdiv($bit, self::WORD_BITS);
        $this->expand($index);
        $this->words[$index] |= $this->bitValue($bit);

        return $this;
    }

    /**
     * Sets the bits from the specified fromIndex (inclusive) to the specified toIndex (exclusive) to true.
     *
     * @param int $fromIndex index of the first bit to be set
     * @param int $toIndex   index after the last bit to be set
     *
     * @throws \InvalidArgumentException if an index in the given range is negative
     */
    public function setRange(int $fromIndex, int $toIndex): self
    {
        return $this->applyRange($fromIndex, $toIndex, fn (int $bit): self => $this->set($bit));
    }

    /**
     * Set multiple bits to true.
     *
     * @param int[] $bits the bits to set
     *
     * @throws \InvalidArgumentException If one of the bit argument is negative
     */
    public function sets(array $bits): self
    {
        foreach ($bits as $bit) {
            $this->set($bit);
        }

        return $this;
    }

    /**
     * Gets the "logical size".
     *
     * @return int the index of the highest set bit plus one
     */
    public function size(): int
    {
        $size = 0;
        /** @psalm-var int $index */
        foreach ($this->words as $index => $word) {
            for ($bit = 0; $bit <= self::WORD_BITS; ++$bit) {
                if (($word & (1 << $bit)) !== 0) {
                    $size = $index * self::WORD_BITS + $bit;
                }
            }
        }

        return 0 === $size ? 0 : $size + 1;
    }

    /**
     * Convert to array.
     *
     * @return int[]
     */
    public function toArray(): array
    {
        return $this->words;
    }

    /**
     * Convert to binary data (string).
     *
     * @see BitSet::fromBinary()
     */
    public function toBinary(): string
    {
        return \pack('V*', ...$this->words);
    }

    public function toBinary2(): string
    {
        $format = '%0' . (\PHP_INT_SIZE * 4) . 'b';
        $callback = fn (string $carry, int $value): string => \sprintf($format, $value) . $carry;
        $result = \array_reduce($this->words, $callback, '');

        return \ltrim($result, '0');
    }

    /**
     * Convert to an array of bits.
     *
     * @return int[]
     */
    public function toIndexes(): array
    {
        $result = [];
        /** @psalm-var int $index */
        foreach ($this->words as $index => $word) {
            for ($bit = 0; $bit <= self::WORD_BITS; ++$bit) {
                if (($word & (1 << $bit)) !== 0) {
                    $result[] = $index * self::WORD_BITS + $bit;
                }
            }
        }

        return $result;
    }

    /**
     * Trim right empty words.
     */
    public function trim(): self
    {
        for ($i = $this->count() - 1; $i >= 0; --$i) {
            if (0 !== $this->words[$i]) {
                $this->words = \array_slice($this->words, 0, $i + 1);

                return $this;
            }
        }

        // reset
        return $this->reset();
    }

    /**
     *  Perform logical XOR.
     */
    public function xor(self $other): self
    {
        return $this->applyLogical($other, static fn (int $value, int $other): int => $value ^ $other);
    }

    /**
     * Apply a logical operation.
     *
     * @param callable(int, int): int $callable
     */
    private function applyLogical(self $other, callable $callable): self
    {
        if ($this->isEqual($other)) {
            return $this;
        }

        $required = $other->count();
        $this->expand($required);

        for ($i = 0; $i < $required; ++$i) {
            $this->words[$i] = $callable($this->words[$i], $other->words[$i]);
        }

        return $this;
    }

    /**
     * Apply  a function to the given range.
     *
     * @param callable(int): self $callable
     */
    private function applyRange(int $fromIndex, int $toIndex, callable $callable): self
    {
        for ($bit = $fromIndex; $bit < $toIndex; ++$bit) {
            $callable($bit);
        }

        return $this;
    }

    private function bitValue(int $bit): int
    {
        return 1 << ($bit % self::WORD_BITS);
    }

    private function count(): int
    {
        return \count($this->words);
    }

    /**
     * Extend words.
     */
    private function expand(int $size): void
    {
        $required = $size + 1;
        $existing = $this->count();
        if ($existing < $required) {
            for ($i = $existing; $i < $required; ++$i) {
                $this->words[$i] = 0;
            }
        }
    }
}
