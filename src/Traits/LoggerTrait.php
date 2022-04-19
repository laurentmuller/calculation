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
     * @throws \Psr\Log\InvalidArgumentException if level is not defined
     */
    public function log(mixed $level, string|\Stringable $message, array $context = []): void
    {
        $this->logger?->log($level, $message, $context);
    }

    /**
     * Logs an alert message.
     */
    public function logAlert(string|\Stringable $message, array $context = []): void
    {
        $this->logger?->alert($message, $context);
    }

    /**
     * Logs a critical message.
     */
    public function logCritical(string|\Stringable $message, array $context = []): void
    {
        $this->logger?->critical($message, $context);
    }

    /**
     * Logs an emergency message.
     */
    public function logEmergency(string|\Stringable $message, array $context = []): void
    {
        $this->logger?->emergency($message, $context);
    }

    /**
     * Logs an error message.
     */
    public function logError(string|\Stringable $message, array $context = []): void
    {
        $this->logger?->error($message, $context);
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
    public function logInfo(string|\Stringable $message, array $context = []): void
    {
        $this->logger?->info($message, $context);
    }

    /**
     * Logs a notice message.
     */
    public function logNotice(string|\Stringable $message, array $context = []): void
    {
        $this->logger?->notice($message, $context);
    }

    /**
     * Logs a warning message.
     */
    public function logWarning(string|\Stringable $message, array $context = []): void
    {
        $this->logger?->warning($message, $context);
    }
}
