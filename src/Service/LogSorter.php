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
class LogSorter
{
    private const COLUMN_CHANNEL = 'channel';

    private const COLUMN_DATE = 'createdAt';

    private const COLUMN_LEVEL = 'level';

    private const COLUMN_MESSAGE = 'message';

    private const COLUMN_USER = 'user';

    /**
     * Constructor.
     *
     * @param string $field     the field to sort
     * @param bool   $ascending true the sort in ascending false for descending
     */
    public function __construct(private readonly string $field, private readonly bool $ascending)
    {
    }

    /**
     * Returns if the given field and ascending sort is the default mode.
     */
    public static function isDefaultSort(string $field, bool $ascending): bool
    {
        return self::COLUMN_DATE === $field && !$ascending;
    }

    /**
     * Sort logs.
     *
     * @param Log[] $logs
     */
    public function sort(array &$logs): bool
    {
        // not data or sort by default
        if (\count($logs) <= 1 || self::isDefaultSort($this->field, $this->ascending)) {
            return false;
        }

        // sort by date ascending
        $dateSorter = $this->getDateSorter();
        if (self::COLUMN_DATE === $this->field) {
            return \uasort($logs, $dateSorter);
        }

        $fieldSorter = $this->getFieldSorter();

        return \uasort($logs, function (Log $a, Log $b) use ($dateSorter, $fieldSorter): int {
            if (null === $fieldSorter || 0 === $result = $fieldSorter($a, $b)) {
                // by date descending (default)
                return -$dateSorter($a, $b);
            }

            return $this->ascending ? $result : -$result;
        });
    }

    /**
     * @return \Closure(Log, Log): int
     */
    private function getDateSorter(): \Closure
    {
        return fn (Log $a, Log $b): int => $a->getCreatedAt() <=> $b->getCreatedAt();
    }

    /**
     * @return \Closure(Log, Log): int|null
     */
    private function getFieldSorter(): ?\Closure
    {
        return match ($this->field) {
            self::COLUMN_LEVEL => fn (Log $a, Log $b): int => $a->getLevel() <=> $b->getLevel(),
            self::COLUMN_CHANNEL => fn (Log $a, Log $b): int => $a->getChannel() <=> $b->getChannel(),
            self::COLUMN_MESSAGE => fn (Log $a, Log $b): int => $a->getMessage() <=> $b->getMessage(),
            self::COLUMN_USER => fn (Log $a, Log $b): int => $a->getUser() <=> $b->getUser(),
            default => null,
        };
    }
}
