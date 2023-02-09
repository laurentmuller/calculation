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
 * Class to filter logs.
 */
class LogFilter
{
    private readonly bool $filterChannel;

    private readonly bool $filterLevel;

    /**
     * @param string $value   the value to search for
     * @param string $channel the channel to search for
     * @param string $level   the level to search for
     */
    public function __construct(private readonly string $value, private readonly string $level, private readonly string $channel)
    {
        $this->filterLevel = !empty($this->level);
        $this->filterChannel = !empty($this->channel);
    }

    /**
     * Apply this filters, if applicable; to the given logs.
     *
     * @param Log[] $logs
     *
     * @return Log[]
     */
    public function apply(array $logs): array
    {
        if ($this->filterLevel) {
            $logs = $this->filterLevel($logs, $this->level);
        }

        if ($this->filterChannel) {
            $logs = $this->filterChannel($logs, $this->channel);
        }

        if (!empty($this->value)) {
            return \array_filter($logs, function (Log $log) {
                return $this->acceptChannel($log)
                    || $this->acceptLevel($log)
                    || $this->acceptDate($log)
                    || $this->acceptMessage($log)
                    || $this->acceptUser($log);
            });
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
        return !empty($value) || !empty($level) || !empty($channel);
    }

    private function acceptChannel(Log $log): bool
    {
        return !$this->filterChannel && $log->isChannel()
            && $this->acceptValue($log->getChannel());
    }

    private function acceptDate(Log $log): bool
    {
        return $this->acceptValue($log->getFormattedDate());
    }

    private function acceptLevel(Log $log): bool
    {
        return !$this->filterLevel && $log->isLevel()
            && $this->acceptValue($log->getLevel());
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
        return null !== $haystack && false !== \stripos($haystack, $this->value);
    }

    /**
     * Filters the log for the given channel.
     *
     * @param Log[] $logs the logs to search in
     *
     * @return Log[] the filtered logs
     */
    private function filterChannel(array $logs, string $value): array
    {
        return \array_filter($logs, static fn (Log $log): bool => 0 === \strcasecmp($value, $log->getChannel()));
    }

    /**
     * Filters the log for the given level.
     *
     * @param Log[] $logs the logs to search in
     *
     * @return Log[] the filtered logs
     */
    private function filterLevel(array $logs, string $value): array
    {
        return \array_filter($logs, static fn (Log $log): bool => 0 === \strcasecmp($value, $log->getLevel()));
    }
}
