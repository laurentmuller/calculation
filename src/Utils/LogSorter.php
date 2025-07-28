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

namespace App\Utils;

use App\Entity\Log;

/**
 * Class to sort logs.
 */
readonly class LogSorter
{
    // sortable field names
    private const COLUMN_CHANNEL = 'channel';
    private const COLUMN_DATE = 'createdAt';
    private const COLUMN_LEVEL = 'level';
    private const COLUMN_MESSAGE = 'message';
    private const COLUMN_USER = 'user';

    /**
     * @param string $field     the field to sort
     * @param bool   $ascending true to sort in ascending mode false to sort in descending mode
     */
    public function __construct(private string $field, private bool $ascending)
    {
    }

    /**
     * Returns if the given field and ascending mode is the default sort.
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
     * Return <code>false</code> if the size of logs is smaller than 2 or if the default sort is set (date descending).
     *
     * @param Log[] $logs the logs to sort
     */
    public function sort(array &$logs): bool
    {
        if (\count($logs) <= 1 || self::isDefaultSort($this->field, $this->ascending)) {
            return false;
        }

        if (self::COLUMN_DATE === $this->field) {
            // date and identifier ascending
            $sorter = static fn (Log $a, Log $b): int => $a->compare($b);
        } else {
            $sorter = $this->getCompositeSorter(
                $this->getFieldSorter(),
                $this->getDateSorter(),
                $this->getIdSorter()
            );
        }

        return \uasort($logs, $sorter);
    }

    /**
     * @param \Closure(Log, Log): int ...$sorters
     *
     * @return \Closure(Log, Log): int
     */
    private function getCompositeSorter(\Closure ...$sorters): \Closure
    {
        return static function (Log $a, Log $b) use ($sorters): int {
            foreach ($sorters as $sorter) {
                $result = $sorter($a, $b);
                if (0 !== $result) {
                    return $result;
                }
            }

            return 0;
        };
    }

    /**
     * Gets the date sorter in descending mode.
     *
     * @return \Closure(Log, Log): int
     */
    private function getDateSorter(): \Closure
    {
        return static fn (Log $a, Log $b): int => $b->getCreatedAt() <=> $a->getCreatedAt();
    }

    /**
     * @return \Closure(Log, Log): int
     */
    private function getFieldSorter(): \Closure
    {
        $order = $this->getOrder($this->ascending);

        return match ($this->field) {
            self::COLUMN_MESSAGE => static fn (Log $a, Log $b): int => $order * ($a->getMessage() <=> $b->getMessage()),
            self::COLUMN_LEVEL => static fn (Log $a, Log $b): int => $order * ($a->getLevel() <=> $b->getLevel()),
            self::COLUMN_CHANNEL => static fn (Log $a, Log $b): int => $order * ($a->getChannel() <=> $b->getChannel()),
            self::COLUMN_USER => static fn (Log $a, Log $b): int => $order * ($a->getUser() <=> $b->getUser()),
            default => $this->getDateSorter(),
        };
    }

    /**
     * Gets the identifier sorter in descending mode.
     *
     * @return \Closure(Log, Log): int
     */
    private function getIdSorter(): \Closure
    {
        return static fn (Log $a, Log $b): int => $b->getId() <=> $a->getId();
    }

    private function getOrder(bool $ascending): int
    {
        return $ascending ? 1 : -1;
    }
}
