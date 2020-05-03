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

namespace App\Command;

use App\Utils\Utils;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trait to write output messages.
 *
 * @author Laurent Muller
 */
trait LoggerTrait
{
    /**
     * The installer name.
     *
     * @var string
     */
    protected $installerName;

    /**
     * The output to write messages.
     *
     * @var OutputInterface
     */
    protected $output;

    /**
     * Concat this installer name and the message.
     *
     * @param string $message the message to output
     *
     * @return string the concated message
     */
    protected function concat(string $message): string
    {
        return $this->getInstallerName() . ': ' . $message;
    }

    /**
     * Gets the installer name.
     *
     * @return string the installer name
     */
    protected function getInstallerName(): string
    {
        if (!$this->installerName) {
            $this->installerName = Utils::getShortName(static::class);
        }

        return $this->installerName;
    }

    /**
     * Returns if the verbose is enabled.
     *
     * @return bool true if verbose
     */
    protected function isVerbose(): bool
    {
        return $this->isOutput() && $this->output->isVerbose();
    }

    /**
     * Returns if the very verbose is enabled.
     *
     * @return bool true if very verbose
     */
    protected function isVeryVerbose(): bool
    {
        return $this->isOutput() && $this->output->isVeryVerbose();
    }

    /**
     * Sets the installer name.
     *
     * @param string $installerName the installer name to set
     */
    protected function setInstallerName(string $installerName): void
    {
        $this->installerName = $installerName;
    }

    /**
     * Writes the given message.
     *
     * @param string $message the message to write
     * @param string $tag     the external tag (info, error, etc)
     */
    protected function write(string $message, string $tag = 'info'): void
    {
        if ($this->isOutput()) {
            $concat = $this->concat($message);
            $this->output->writeln("<$tag>$concat</$tag>");
        }
    }

    /**
     * Writes the given error message.
     *
     * @param string $message the message to write
     */
    protected function writeError(string $message): void
    {
        $this->write($message, 'error');
    }

    /**
     * Writes the given message.
     *
     * @param string $message the message to write
     */
    protected function writeVerbose(string $message): void
    {
        if ($this->isVerbose()) {
            $this->write($message);
        }
    }

    /**
     * Writes the given message.
     *
     * @param string $message the message to write
     */
    protected function writeVeryVerbose(string $message): void
    {
        if ($this->isVeryVerbose()) {
            $this->write($message);
        }
    }

    /**
     * Returns if this output is not null.
     *
     * @return bool true if not null
     */
    private function isOutput(): bool
    {
        return null !== $this->output;
    }
}
