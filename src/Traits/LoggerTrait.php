<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Traits;

use App\Util\Utils;
use Psr\Log\LoggerAwareTrait;

/**
 * Trait to log messages within a LoggerInterface instance.
 *
 * @author Laurent Muller
 */
trait LoggerTrait
{
    use LoggerAwareTrait;

    /**
     * Logs with an arbitrary level message.
     *
     * @param mixed $level
     *
     * @throws \Psr\Log\InvalidArgumentException if level is not defined
     */
    public function log($level, string $message, array $context = []): void
    {
        if (null !== $this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }

    /**
     * Logs an alert message.
     */
    public function logAlert(string $message, array $context = []): void
    {
        if (null !== $this->logger) {
            $this->logger->alert($message, $context);
        }
    }

    /**
     * Logs a critical message.
     */
    public function logCritical(string $message, array $context = []): void
    {
        if (null !== $this->logger) {
            $this->logger->critical($message, $context);
        }
    }

    /**
     * Logs an emergency message.
     */
    public function logEmergency(string $message, array $context = []): void
    {
        if (null !== $this->logger) {
            $this->logger->emergency($message, $context);
        }
    }

    /**
     * Logs an error message.
     */
    public function logError(string $message, array $context = []): void
    {
        if (null !== $this->logger) {
            $this->logger->error($message, $context);
        }
    }

    /**
     * Logs the given exception as an error message.
     */
    public function logException(\Exception $e, ?string $message = null): void
    {
        $context = Utils::getExceptionContext($e);
        $this->logError($message ?? $e->getMessage(), $context);
    }

    /**
     * Logs an information message.
     */
    public function logInfo(string $message, array $context = []): void
    {
        if (null !== $this->logger) {
            $this->logger->info($message, $context);
        }
    }

    /**
     * Logs a notice message.
     */
    public function logNotice(string $message, array $context = []): void
    {
        if (null !== $this->logger) {
            $this->logger->notice($message, $context);
        }
    }

    /**
     * Logs a warning message.
     */
    public function logWarning(string $message, array $context = []): void
    {
        if (null !== $this->logger) {
            $this->logger->warning($message, $context);
        }
    }
}
