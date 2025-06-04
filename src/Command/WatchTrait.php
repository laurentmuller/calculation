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

use App\Utils\FileUtils;
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
        $duration = \sprintf('%d ms', $event->getDuration());
        $memory = FileUtils::formatSize($event->getMemory());

        return \sprintf('Duration: %s, Memory: %s', $duration, $memory);
    }

    private function getStopwatch(): Stopwatch
    {
        return $this->stopWatch ??= new Stopwatch();
    }
}
