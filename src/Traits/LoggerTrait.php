<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Traits;

use Psr\Log\LoggerInterface;

/**
 * Trait to log messages within a LoggerInterface instance.
 *
 * @author Laurent Muller
 */
trait LoggerTrait
{
    /**
     * The logger service.
     *
     * @var LoggerInterface
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
        if ($this->logger) {
            $this->log($level, $message.$context);
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
        if ($this->logger) {
            $this->logger->alert($message, $context);
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
        if ($this->logger) {
            $this->logger->critical($message, $context);
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
        if ($this->logger) {
            $this->logger->emergency($message, $context);
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
        if ($this->logger) {
            $this->logger->error($message, $context);
        }
    }

    /**
     * Logs an information message.
     *
     * @param string $message the message
     * @param array  $context the context
     */
    public function logInfo(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->info($message, $context);
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
        if ($this->logger) {
            $this->logger->warning($message, $context);
        }
    }

    /**
     * Logs a notice message.
     *
     * @param string $message the message
     * @param array  $context the context
     */
    public function notice(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->notice($message, $context);
        }
    }
}
