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

/**
 * Class to sort logs.
 */
readonly class LogSorterService
{
    /**
     * The channel column name.
     */
    public const COLUMN_CHANNEL = 'channel';
    /**
     * The created date column name.
     */
    public const COLUMN_DATE = 'createdAt';
    /**
     * The level column name.
     */
    public const COLUMN_LEVEL = 'level';
    /**
     * The message column name.
     */
    public const COLUMN_MESSAGE = 'message';
    /**
     * The user column name.
     */
    public const COLUMN_USER = 'user';

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
        if (\count($logs) <= 1 || self::isDefaultSort($this->field, $this->ascending)) {
            return false;
        }

        $sorter = match ($this->field) {
            self::COLUMN_CHANNEL => $this->getChannelSorter(),
            self::COLUMN_LEVEL => $this->getLevelSorter(),
            self::COLUMN_MESSAGE => $this->getMessageSorter(),
            self::COLUMN_USER => $this->getUserSorter(),
            default => $this->getDateSorter(), // date
        };

        return \uasort($logs, $sorter);
    }

    /**
     * @param callable(Log): ?string $callable
     *
     * @return \Closure(Log, Log): int
     */
    private function createSorter(callable $callable): \Closure
    {
        $order = $this->ascending ? 1 : -1;

        // @phpstan-ignore ternary.shortNotAllowed
        return static fn (Log $a, Log $b): int => $order * ($callable($a) <=> $callable($b)) ?: $b->compare($a);
    }

    /**
     * Compare by channel, date descending and identifier descending.
     *
     * @return \Closure(Log, Log): int
     */
    private function getChannelSorter(): \Closure
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
     * @return \Closure(Log, Log): int
     */
    private function getLevelSorter(): \Closure
    {
        return $this->createSorter(static fn (Log $log): string => $log->getLevel());
    }

    /**
     * Compare by message, date descending and identifier descending.
     *
     * @return \Closure(Log, Log): int
     */
    private function getMessageSorter(): \Closure
    {
        return $this->createSorter(static fn (Log $log): string => $log->getMessage());
    }

    /**
     * Compare by user, date descending and identifier descending.
     *
     * @return \Closure(Log, Log): int
     */
    private function getUserSorter(): \Closure
    {
        return $this->createSorter(static fn (Log $log): ?string => $log->getUser());
    }
}
