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

namespace App\Pivot\Formatter;

/**
 * Map an array key to the corresponding string.
 */
readonly class ArrayFormatter implements FormatterInterface
{
    /**
     * @param non-empty-array<int, string> $mapping
     */
    public function __construct(private array $mapping)
    {
    }

    /**
     * @throws \InvalidArgumentException if the value is not in this mapping keys
     */
    #[\Override]
    public function format(int|float|string $value): string
    {
        return $this->mapping[(int) $value] ?? throw new \InvalidArgumentException(\sprintf('Invalid value: %d, allowed values %s.', $value, $this->getRange()));
    }

    private function getRange(): string
    {
        $Keys = \array_keys($this->mapping);

        return \sprintf('[%d..%d]', \min($Keys), \max($Keys));
    }
}
