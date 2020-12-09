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
use Psr\Log\LoggerInterface;

/**
 * Trait to log messages within a LoggerInterface instance.
 *
 * @author Laurent Muller
 */
trait LoggerTrait
{
    /**
     * The logger instance.
     *
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * Logs with an arbitrary level message.
     *
     * @param mixed  $level   the level
     * @param string $message the message
     * @param array  $context the context
     */
    public function log($level, string $message, array $context = []): void
    {
        if ($logger = $this->doGetLogger()) {
            $logger->log($level, $message, $context);
        }
    }

    /**
     * Logs an alert message.
     *
     * @param string $message the message
     * @param array  $context the context
     */
    public function logAlert(string $message, array $context = []): void
    {
        if ($logger = $this->doGetLogger()) {
            $logger->alert($message, $context);
        }
    }

    /**
     * Logs a critical message.
     *
     * @param string $message the message
     * @param array  $context the context
     */
    public function logCritical(string $message, array $context = []): void
    {
        if ($logger = $this->doGetLogger()) {
            $logger->critical($message, $context);
        }
    }

    /**
     * Logs a system message.
     *
     * @param string $message the message
     * @param array  $context the context
     */
    public function logEmergency(string $message, array $context = []): void
    {
        if ($logger = $this->doGetLogger()) {
            $logger->emergency($message, $context);
        }
    }

    /**
     * Logs an error message.
     *
     * @param string $message the message
     * @param array  $context the context
     */
    public function logError(string $message, array $context = []): void
    {
        if ($logger = $this->doGetLogger()) {
            $logger->error($message, $context);
        }
    }

    /**
     * Logs the given exception as an error message.
     *
     * @param \Exception $e       the exception to log
     * @param string     $message the optional message or null to use the exception message
     */
    public function logException(\Exception $e, string $message = null): void
    {
        $context = Utils::getExceptionContext($e);
        $this->logError($message ?? $e->getMessage(), $context);
    }

    /**
     * Logs an information message.
     *
     * @param string $message the message
     * @param array  $context the context
     */
    public function logInfo(string $message, array $context = []): void
    {
        if ($logger = $this->doGetLogger()) {
            $logger->info($message, $context);
        }
    }

    /**
     * Logs a notice message.
     *
     * @param string $message the message
     * @param array  $context the context
     */
    public function logNotice(string $message, array $context = []): void
    {
        if ($logger = $this->doGetLogger()) {
            $logger->notice($message, $context);
        }
    }

    /**
     * Logs a warning message.
     *
     * @param string $message the message
     * @param array  $context the context
     */
    public function logWarning(string $message, array $context = []): void
    {
        if ($logger = $this->doGetLogger()) {
            $logger->warning($message, $context);
        }
    }

    /**
     * Gets the logger.
     *
     * @return LoggerInterface|null the logger, if found; null otherwise
     */
    protected function doGetLogger(): ?LoggerInterface
    {
        if (!$this->logger && \method_exists($this, 'getLogger')) {
            return $this->logger = $this->getLogger();
        }

        return $this->logger;
    }
}
