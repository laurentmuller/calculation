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
    private string $searchChannel;
    private string $searchLevel;
    private string $searchValue;

    /**
     * @param string $value   the value to search for
     * @param string $level   the level to search for
     * @param string $channel the channel to search for
     */
    public function __construct(string $value = '', string $level = '', string $channel = '')
    {
        $this->searchValue = \trim($value);
        $this->searchLevel = \trim($level);
        $this->searchChannel = \trim($channel);
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
        $isChannel = StringUtils::isString($this->searchChannel);
        $isLevel = StringUtils::isString($this->searchLevel);
        $isValue = StringUtils::isString($this->searchValue);
        if (!$isChannel && !$isLevel && !$isValue) {
            return $logs;
        }

        $result = [];
        foreach ($logs as $log) {
            if ($isChannel && !$this->matchChannel($log)) {
                continue;
            }
            if ($isLevel && !$this->matchLevel($log)) {
                continue;
            }
            if ($isValue && !$this->matchValue($log)) {
                continue;
            }
            $result[] = $log;
        }

        return $result;
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
    public static function isFilter(string $value = '', string $level = '', string $channel = ''): bool
    {
        return null !== StringUtils::trim($value)
            || null !== StringUtils::trim($level)
            || null !== StringUtils::trim($channel);
    }

    private function acceptChannel(Log $log): bool
    {
        return $this->acceptValue($log->getChannel());
    }

    private function acceptDate(Log $log): bool
    {
        return $this->acceptValue($log->getFormattedDate());
    }

    private function acceptLevel(Log $log): bool
    {
        return $this->acceptValue($log->getLevel());
    }

    private function acceptMessage(Log $log): bool
    {
        return $this->acceptValue($log->getMessage());
    }

    private function acceptUser(Log $log): bool
    {
        return $this->acceptValue($log->getUser());
    }

    private function acceptValue(?string $value): bool
    {
        return null !== $value && false !== \stripos($value, $this->searchValue);
    }

    private function matchChannel(Log $log): bool
    {
        return StringUtils::equalIgnoreCase($this->searchChannel, $log->getChannel());
    }

    private function matchLevel(Log $log): bool
    {
        return StringUtils::equalIgnoreCase($this->searchLevel, $log->getLevel());
    }

    private function matchValue(Log $log): bool
    {
        return $this->acceptChannel($log)
                || $this->acceptLevel($log)
                || $this->acceptDate($log)
                || $this->acceptMessage($log)
                || $this->acceptUser($log);
    }
}
