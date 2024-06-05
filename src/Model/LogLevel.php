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

use App\Traits\LogLevelTrait;

class LogLevel implements \Countable, \Stringable
{
    use LogLevelTrait;

    private int $count = 0;

    public function __construct(string $level)
    {
        $this->setLevel($level);
    }

    public function __toString(): string
    {
        return $this->getLevel();
    }

    public function count(): int
    {
        return $this->count;
    }

    public function increment(int $value = 1): self
    {
        $this->count += $value;

        return $this;
    }

    public static function instance(string $level): self
    {
        return new self($level);
    }
}
