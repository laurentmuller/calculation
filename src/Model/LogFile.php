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
     * @var array<string, int>
     */
    private array $channels = [];

    /**
     * The log file name.
     */
    private string $file = '';

    /**
     * @var array<string, int>
     */
    private array $levels = [];

    /**
     * @var Log[]
     */
    private array $logs = [];

    /**
     * Constructor.
     *
     * @param ?string $file the optional log file name
     */
    public function __construct(?string $file = null)
    {
        if (null !== $file) {
            $this->setFile($file);
        }
    }

    /**
     * Add the given log to this list of logs. The levels and channels are updated accordingly.
     */
    public function addLog(Log $log): self
    {
        $id = \count($this->logs);
        $log->setId($id);
        $this->logs[$id] = $log;
        $this->addLevel($log->getLevel());
        $this->addChannel($log->getChannel());

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        return \count($this->logs);
    }

    /**
     * @return array<string, int>
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
     * @return array<string, int>
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
        return empty($this->logs);
    }

    public function setFile(string $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function sort(): self
    {
        if (!$this->isEmpty()) {
            \ksort($this->levels, \SORT_LOCALE_STRING);
            \ksort($this->channels, \SORT_LOCALE_STRING);
            \uasort($this->logs, static fn (Log $a, Log $b): int => $b->getCreatedAt() <=> $a->getCreatedAt());
        }

        return $this;
    }

    private function addChannel(string $channel): void
    {
        $this->channels[$channel] = 1 + ($this->channels[$channel] ?? 0);
    }

    private function addLevel(string $level): void
    {
        $this->levels[$level] = 1 + ($this->levels[$level] ?? 0);
    }
}
