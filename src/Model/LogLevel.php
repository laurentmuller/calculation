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
use App\Traits\LogLevelTrait;
use Monolog\Level;
use Psr\Log\LogLevel as PsrLevel;

/**
 * @implements ComparableInterface<LogLevel>
 */
class LogLevel implements \Countable, \Stringable, ComparableInterface
{
    use LogLevelTrait;

    /** @phpstan-var non-negative-int */
    private int $count = 0;

    /**
     * @phpstan-param PsrLevel::* $level
     */
    public function __construct(string $level)
    {
        $this->setLevel($level);
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->getLevel();
    }

    #[\Override]
    public function compare(ComparableInterface $other): int
    {
        return $this->getLevelIndex() <=> $other->getLevelIndex();
    }

    #[\Override]
    public function count(): int
    {
        return $this->count;
    }

    public function getLevelIndex(): int
    {
        return Level::fromName($this->level)->value;
    }

    /**
     * @phpstan-param positive-int $value
     */
    public function increment(int $value = 1): self
    {
        $this->count += $value;

        return $this;
    }

    /**
     * @phpstan-param PsrLevel::* $level
     */
    public static function instance(string $level): self
    {
        return new self($level);
    }
}
