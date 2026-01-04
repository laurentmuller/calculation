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
 * @extends AbstractLogCounter<LogChannel>
 */
class LogChannel extends AbstractLogCounter
{
    use LogChannelTrait;

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

    public static function instance(string $channel): self
    {
        return new self($channel);
    }
}
