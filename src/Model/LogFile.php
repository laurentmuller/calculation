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

use App\Entity\Log;

/**
 * Contains information about a log file.
 */
class LogFile implements \Countable
{
    /**
     * @var array<string, LogChannel>
     */
    private array $channels = [];

    /**
     * @var array<string, LogLevel>
     */
    private array $levels = [];

    /**
     * @var Log[]
     */
    private array $logs = [];

    public function __construct(private readonly string $file)
    {
    }

    /**
     * Add the given log to this list of logs. The levels and channels are updated accordingly.
     */
    public function addLog(Log $log): self
    {
        $this->logs[(int) $log->getId()] = $log;
        $this->updateLevels($log->getLevel());
        $this->updateChannels($log->getChannel());

        return $this;
    }

    public function count(): int
    {
        return \count($this->logs);
    }

    /**
     * @return array<string, LogChannel>
     */
    public function getChannels(): array
    {
        return $this->channels;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @return array<string, LogLevel>
     */
    public function getLevels(): array
    {
        return $this->levels;
    }

    public function getLog(int $id): ?Log
    {
        return $this->logs[$id] ?? null;
    }

    /**
     * @return Log[]
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    public function isEmpty(): bool
    {
        return [] === $this->logs;
    }

    /**
     * Sorts levels, channels and logs by date descending.
     */
    public function sort(): self
    {
        if (!$this->isEmpty()) {
            \ksort($this->levels);
            \ksort($this->channels);
            \uasort($this->logs, static fn (Log $a, Log $b): int => $b->compare($a));
        }

        return $this;
    }

    private function updateChannels(string $name): void
    {
        if (!\array_key_exists($name, $this->channels)) {
            $this->channels[$name] = LogChannel::instance($name);
        }
        $this->channels[$name]->increment();
    }

    private function updateLevels(string $name): void
    {
        if (!\array_key_exists($name, $this->levels)) {
            $this->levels[$name] = LogLevel::instance($name);
        }

        $this->levels[$name]->increment();
    }
}
