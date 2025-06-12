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

namespace App\Command;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Trait to compute duration and memory usage.
 */
trait WatchTrait
{
    private ?Stopwatch $stopWatch = null;

    protected function start(): void
    {
        $this->getStopwatch()->start('run');
    }

    protected function stop(): string
    {
        $event = $this->getStopwatch()->stop('run');
        $duration = $this->formatDuration($event->getDuration());
        $memory = $this->formatMemory($event->getMemory());

        return \sprintf('Duration: %s, Memory: %s', $duration, $memory);
    }

    private function formatDuration(float $duration): string
    {
        return Helper::formatTime($duration / 1000.0);
    }

    private function formatMemory(int $memory): string
    {
        return Helper::formatMemory($memory);
    }

    private function getStopwatch(): Stopwatch
    {
        return $this->stopWatch ??= new Stopwatch();
    }
}
