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
            return \uasort($logs, $this->getDateSorter(true));
        }

        $fieldSorter = $this->getFieldSorter();
        $dateSorter = $this->getDateSorter(false);

        return \uasort($logs, function (Log $a, Log $b) use ($fieldSorter, $dateSorter): int {
            $result = $fieldSorter($a, $b);
            if (0 === $result) {
                return $dateSorter($a, $b);
            }

            return $result;
        });
    }

    /**
     * @return \Closure(Log, Log): int
     */
    private function getDateSorter(bool $ascending): \Closure
    {
        $order = $ascending ? 1 : -1;

        return fn (Log $a, Log $b): int => $order * ($a->getCreatedAt() <=> $b->getCreatedAt());
    }

    /**
     * @return \Closure(Log, Log): int
     */
    private function getFieldSorter(): \Closure
    {
        $order = $this->ascending ? 1 : -1;

        return match ($this->field) {
            self::COLUMN_LEVEL => fn (Log $a, Log $b): int => $order * ($a->getLevel() <=> $b->getLevel()),
            self::COLUMN_CHANNEL => fn (Log $a, Log $b): int => $order * ($a->getChannel() <=> $b->getChannel()),
            self::COLUMN_MESSAGE => fn (Log $a, Log $b): int => $order * ($a->getMessage() <=> $b->getMessage()),
            self::COLUMN_USER => fn (Log $a, Log $b): int => $order * ($a->getUser() <=> $b->getUser()),
            default => $this->getDateSorter(false),
        };
    }
}
