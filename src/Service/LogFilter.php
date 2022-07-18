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
use App\Util\Utils;

/**
 * Class to filter logs.
 */
class LogFilter
{
    /**
     * @param string $value       the value to search for (must not be empty)
     * @param bool   $skipChannel true to skip search in channel
     * @param bool   $skipLevel   true to skip search in level
     */
    public function __construct(private readonly string $value, private readonly bool $skipChannel, private readonly bool $skipLevel)
    {
    }

    /**
     * @param Log[] $logs
     *
     * @return Log[]
     */
    public function filter(array $logs): array
    {
        return \array_filter($logs, function (Log $log) {
            return $this->acceptChannel($log)
                  || $this->acceptLevel($log)
                  || $this->acceptDate($log)
                  || $this->acceptMessage($log)
                  || $this->acceptUser($log);
        });
    }

    private function acceptChannel(Log $log): bool
    {
        return !$this->skipChannel && $log->isChannel()
            && $this->acceptValue($log->getChannel());
    }

    private function acceptDate(Log $log): bool
    {
        return $this->acceptValue($log->getFormattedDate());
    }

    private function acceptLevel(Log $log): bool
    {
        return !$this->skipLevel && $log->isLevel()
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
        return null !== $haystack && Utils::contains($haystack, $this->value, true);
    }
}
