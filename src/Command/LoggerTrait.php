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

namespace App\Command;

/**
 * Trait to write output messages.
 */
trait LoggerTrait
{
    /**
     * The symfony style.
     */
    private ?SymfonyStyle $io = null;

    /**
     * Returns whether verbosity is verbose (-v).
     */
    protected function isVerbose(): bool
    {
        return $this->io?->isVerbose() ?? false;
    }

    /**
     * Returns whether verbosity is very verbose (-vv).
     */
    protected function isVeryVerbose(): bool
    {
        return $this->io?->isVeryVerbose() ?? false;
    }

    /**
     * Writes the given error message.
     */
    protected function writeError(string $message): void
    {
        $this->io?->error($message);
    }

    /**
     * Writes the given information message.
     */
    protected function writeln(string $message, string $style = 'info'): void
    {
        $this->io?->writeln("<$style>$message</>");
    }

    /**
     * Writes the given note message.
     */
    protected function writeNote(string $message): void
    {
        $this->io?->note($message);
    }

    /**
     * Writes the given success message.
     */
    protected function writeSuccess(string $message): void
    {
        $this->io?->success($message);
    }

    /**
     * Writes the given information message whether verbosity is verbose (-v).
     */
    protected function writeVerbose(string $message, string $style = 'info'): void
    {
        if ($this->isVerbose()) {
            $this->writeln($message, $style);
        }
    }

    /**
     * Writes the given information message whether verbosity is very verbose (-vv).
     */
    protected function writeVeryVerbose(string $message, string $style = 'info'): void
    {
        if ($this->isVeryVerbose()) {
            $this->writeln($message, $style);
        }
    }

    /**
     * Writes the given warning message.
     */
    protected function writeWarning(string $message): void
    {
        $this->io?->warning($message);
    }
}
