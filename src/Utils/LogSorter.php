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
     * Constructor.
     *
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
     * Sort logs.
     *
     * @param Log[] $logs
     */
    public function sort(array &$logs): bool
    {
        if (\count($logs) <= 1 || self::isDefaultSort($this->field, $this->ascending)) {
            return false;
        }
        $dateSorter = $this->getDateSorter();
        if (self::COLUMN_DATE === $this->field) {
            return \uasort($logs, $dateSorter);
        }
        $order = $this->ascending ? 1 : -1;
        $fieldSorter = $this->getFieldSorter();

        return \uasort($logs, function (Log $a, Log $b) use ($dateSorter, $fieldSorter, $order): int {
            if (!$fieldSorter instanceof \Closure) {
                return -$dateSorter($a, $b);
            }
            $result = $order * $fieldSorter($a, $b);
            if (0 === $result) {
                return -$dateSorter($a, $b);
            }

            return $result;
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
     * @return ?\Closure(Log, Log): int
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
