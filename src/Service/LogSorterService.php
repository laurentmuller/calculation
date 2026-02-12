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

namespace App\Service;

use App\Entity\Log;
use App\Traits\ClosureSortTrait;

/**
 * Class to sort logs.
 */
readonly class LogSorterService
{
    use ClosureSortTrait;

    /** The channel column name. */
    public const string COLUMN_CHANNEL = 'channel';
    /** The created date column name. */
    public const string COLUMN_DATE = 'createdAt';
    /** The level column name. */
    public const string COLUMN_LEVEL = 'level';
    /** The message column name. */
    public const string COLUMN_MESSAGE = 'message';
    /** The user column name. */
    public const string COLUMN_USER = 'user';

    /**
     * @param string $field     the field to sort
     * @param bool   $ascending true to sort in ascending mode false to sort in descending mode
     *
     * @phpstan-param self::COLUMN_* $field
     */
    public function __construct(private string $field, private bool $ascending)
    {
    }

    /**
     * Creates a new instance.
     *
     * @param string $field     the field to sort
     * @param bool   $ascending true to sort in ascending mode false to sort in descending mode
     *
     * @phpstan-param self::COLUMN_* $field
     */
    public static function instance(string $field, bool $ascending): self
    {
        return new self($field, $ascending);
    }

    /**
     * Returns if the given field and ascending mode is the default sort.
     *
     * @phpstan-param self::COLUMN_* $field
     *
     * @return bool <code>true</code> if this sort is in date descending; <code>false</code> otherwise
     */
    public static function isDefaultSort(string $field, bool $ascending): bool
    {
        return self::COLUMN_DATE === $field && !$ascending;
    }

    /**
     * Sort the given logs.
     *
     * @param Log[] $logs the logs to sort
     *
     * @return bool <code>false</code> if the size of logs is smaller than 2 or
     *              if the default sort is set (date descending)
     */
    public function sort(array &$logs): bool
    {
        if (\count($logs) < 2 || self::isDefaultSort($this->field, $this->ascending)) {
            return false;
        }

        $sorter = match ($this->field) {
            self::COLUMN_CHANNEL => $this->getChannelSorter(),
            self::COLUMN_LEVEL => $this->getLevelSorter(),
            self::COLUMN_MESSAGE => $this->getMessageSorter(),
            self::COLUMN_USER => $this->getUserSorter(),
            default => $this->getDateSorter(),
        };

        if ($sorter instanceof \Closure) {
            return \uasort($logs, $sorter);
        }

        return $this->sortByClosures($logs, ...$sorter);
    }

    /**
     * @param callable(Log): ?string $callable
     *
     * @return array<\Closure(Log, Log): int>
     */
    private function createSorter(callable $callable): array
    {
        $order = $this->ascending ? 1 : -1;

        return [
            static fn (Log $a, Log $b): int => $order * ($callable($a) <=> $callable($b)),
            static fn (Log $a, Log $b): int => $b->compare($a),
        ];
    }

    /**
     * Compare by channel, date descending and identifier descending.
     *
     * @return array<\Closure(Log, Log): int>
     */
    private function getChannelSorter(): array
    {
        return $this->createSorter(static fn (Log $log): string => $log->getChannel());
    }

    /**
     * Compare by date ascending and identifier ascending.
     *
     * @return \Closure(Log, Log): int
     */
    private function getDateSorter(): \Closure
    {
        return static fn (Log $a, Log $b): int => $a->compare($b);
    }

    /**
     * Compare by level, date descending and identifier descending.
     *
     * @return array<\Closure(Log, Log): int>
     */
    private function getLevelSorter(): array
    {
        return $this->createSorter(static fn (Log $log): string => $log->getLevel());
    }

    /**
     * Compare by message, date descending and identifier descending.
     *
     * @return array<\Closure(Log, Log): int>
     */
    private function getMessageSorter(): array
    {
        return $this->createSorter(static fn (Log $log): string => $log->getMessage());
    }

    /**
     * Compare by user, date descending and identifier descending.
     *
     * @return array<\Closure(Log, Log): int>
     */
    private function getUserSorter(): array
    {
        return $this->createSorter(static fn (Log $log): ?string => $log->getUser());
    }
}
