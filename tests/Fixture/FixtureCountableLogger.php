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

namespace App\Tests\Fixture;

use Psr\Log\AbstractLogger;

class FixtureCountableLogger extends AbstractLogger implements \Countable
{
    /** @var int<0, max> */
    private int $count = 0;

    #[\Override]
    public function count(): int
    {
        return $this->count;
    }

    #[\Override]
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        ++$this->count;
    }
}
