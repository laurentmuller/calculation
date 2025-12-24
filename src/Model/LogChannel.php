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

namespace App\Model;

use App\Interfaces\ComparableInterface;
use App\Traits\LogChannelTrait;

/**
 * @implements ComparableInterface<LogChannel>
 */
class LogChannel implements \Countable, \Stringable, ComparableInterface
{
    use LogChannelTrait;

    /** @phpstan-var non-negative-int */
    private int $count = 0;

    public function __construct(string $channel)
    {
        $this->setChannel($channel);
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->getChannel();
    }

    #[\Override]
    public function compare(ComparableInterface $other): int
    {
        return $this->getChannel() <=> $other->getChannel();
    }

    #[\Override]
    public function count(): int
    {
        return $this->count;
    }

    /**
     * @phpstan-param positive-int $value
     */
    public function increment(int $value = 1): self
    {
        $this->count += $value;

        return $this;
    }

    public static function instance(string $channel): self
    {
        return new self($channel);
    }
}
