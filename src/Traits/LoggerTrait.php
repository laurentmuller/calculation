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

use Psr\Log\LoggerInterface;

/**
 * Trait to log messages within a LoggerInterface instance.
 */
trait LoggerTrait
{
    use ExceptionContextTrait;

    /**
     * Gets the logger.
     */
    abstract public function getLogger(): LoggerInterface;

    /**
     * Logs with an arbitrary level message.
     *
     * @throws \Psr\Log\InvalidArgumentException if the level is not defined
     */
    public function log(mixed $level, string|\Stringable $message, array $context = []): void
    {
        $this->getLogger()->log($level, $message, $context);
    }

    /**
     * Logs an alert message.
     */
    public function logAlert(string|\Stringable $message, array $context = []): void
    {
        $this->getLogger()->alert($message, $context);
    }

    /**
     * Logs a critical message.
     */
    public function logCritical(string|\Stringable $message, array $context = []): void
    {
        $this->getLogger()->critical($message, $context);
    }

    /**
     * Logs a debug message.
     */
    public function logDebug(string|\Stringable $message, array $context = []): void
    {
        $this->getLogger()->debug($message, $context);
    }

    /**
     * Logs an emergency message.
     */
    public function logEmergency(string|\Stringable $message, array $context = []): void
    {
        $this->getLogger()->emergency($message, $context);
    }

    /**
     * Logs an error message.
     */
    public function logError(string|\Stringable $message, array $context = []): void
    {
        $this->getLogger()->error($message, $context);
    }

    /**
     * Logs the given exception as an error message.
     */
    public function logException(\Throwable $e, ?string $message = null): void
    {
        $message ??= $e->getMessage();
        $context = $this->getExceptionContext($e);
        $this->logError($message, $context);
    }

    /**
     * Logs an information message.
     */
    public function logInfo(string|\Stringable $message, array $context = []): void
    {
        $this->getLogger()->info($message, $context);
    }

    /**
     * Logs a notice message.
     */
    public function logNotice(string|\Stringable $message, array $context = []): void
    {
        $this->getLogger()->notice($message, $context);
    }

    /**
     * Logs a warning message.
     */
    public function logWarning(string|\Stringable $message, array $context = []): void
    {
        $this->getLogger()->warning($message, $context);
    }
}
