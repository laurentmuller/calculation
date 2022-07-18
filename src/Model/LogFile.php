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

    private string $file = '';

    /**
     * @var array<string, int>
     */
    private array $levels = [];

    /**
     * @var Log[]
     */
    private array $logs = [];

    public function addLog(Log $log): self
    {
        $id = \count($this->logs);
        $log->setId($id);
        $this->logs[$id] = $log;
        $this->incrementLevel((string) $log->getLevel());
        $this->incrementChannel((string) $log->getChannel());

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
        }

        return $this;
    }

    private function incrementChannel(string $channel): void
    {
        $value = $this->channels[$channel] ?? 0;
        $this->channels[$channel] = $value + 1;
    }

    private function incrementLevel(string $level): void
    {
        $value = $this->levels[$level] ?? 0;
        $this->levels[$level] = $value + 1;
    }
}
