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
 * Class to filter logs.
 */
readonly class LogFilter
{
    private bool $isFilterChannel;
    private bool $isFilterLevel;
    private bool $isFilterValue;
    private string $searchChannel;
    private string $searchLevel;
    private string $searchValue;

    /**
     * @param string $value   the value to search for
     * @param string $level   the level to search for
     * @param string $channel the channel to search for
     */
    public function __construct(string $value, string $level, string $channel)
    {
        $this->searchValue = \trim($value);
        $this->searchLevel = \trim($level);
        $this->searchChannel = \trim($channel);
        $this->isFilterValue = '' !== $this->searchValue;
        $this->isFilterLevel = '' !== $this->searchLevel;
        $this->isFilterChannel = '' !== $this->searchChannel;
    }

    /**
     * Filters to the given logs.
     *
     * @param Log[] $logs
     *
     * @return Log[]
     */
    public function filter(array $logs): array
    {
        if (!self::isFilter($this->searchValue, $this->searchLevel, $this->searchChannel)) {
            return $logs;
        }
        if ($this->isFilterLevel) {
            $logs = $this->filterLevel($logs);
        }
        if ($this->isFilterChannel) {
            $logs = $this->filterChannel($logs);
        }
        if ($this->isFilterValue) {
            return $this->filterValue($logs);
        }

        return $logs;
    }

    /**
     * Returns if a filter must be applied.
     *
     * @param string $value   the value to search for
     * @param string $channel the channel to search for
     * @param string $level   the level to search for
     *
     * @return bool true if a filter must be applied; false otherwise
     */
    public static function isFilter(string $value, string $level, string $channel): bool
    {
        return '' !== \trim($value) || '' !== \trim($level) || '' !== \trim($channel);
    }

    private function acceptChannel(Log $log): bool
    {
        return !$this->isFilterChannel && $this->acceptValue($log->getChannel());
    }

    private function acceptDate(Log $log): bool
    {
        return $this->acceptValue($log->getFormattedDate());
    }

    private function acceptLevel(Log $log): bool
    {
        return !$this->isFilterLevel && $this->acceptValue($log->getLevel());
    }

    private function acceptMessage(Log $log): bool
    {
        return $this->acceptValue($log->getMessage());
    }

    private function acceptUser(Log $log): bool
    {
        return $this->acceptValue($log->getUser());
    }

    private function acceptValue(?string $haystack): bool
    {
        return null !== $haystack && false !== \stripos($haystack, $this->searchValue);
    }

    /**
     * Filters the given logs for this channel.
     *
     * @param Log[] $logs the logs to search in
     *
     * @return Log[] the filtered logs
     */
    private function filterChannel(array $logs): array
    {
        return \array_filter(
            $logs,
            fn (Log $log): bool => StringUtils::equalIgnoreCase($this->searchChannel, $log->getChannel())
        );
    }

    /**
     * Filters the given logs for this level.
     *
     * @param Log[] $logs the logs to search in
     *
     * @return Log[] the filtered logs
     */
    private function filterLevel(array $logs): array
    {
        return \array_filter(
            $logs,
            fn (Log $log): bool => StringUtils::equalIgnoreCase($this->searchLevel, $log->getLevel())
        );
    }

    /**
     * Filter the given logs for this search value.
     *
     * @param Log[] $logs the logs to search in
     *
     * @return Log[] the filtered logs
     */
    private function filterValue(array $logs): array
    {
        return \array_filter(
            $logs,
            fn (Log $log): bool => $this->acceptChannel($log)
            || $this->acceptLevel($log)
            || $this->acceptDate($log)
            || $this->acceptMessage($log)
            || $this->acceptUser($log)
        );
    }
}
