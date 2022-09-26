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

use Symfony\Component\Console\Style\SymfonyStyle;

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
        return $this->io && $this->io->isVerbose();
    }

    /**
     * Returns whether verbosity is very verbose (-vv).
     */
    protected function isVeryVerbose(): bool
    {
        return $this->io && $this->io->isVeryVerbose();
    }

    /**
     * Writes the given information message.
     */
    protected function write(string $message, string $style = ''): void
    {
        if (null !== $this->io) {
            if ('' !== $style) {
                $this->io->writeln("<$style>$message</>");
            } else {
                $this->io->writeln("<info>$message</info>");
            }
        }
    }

    /**
     * Writes the given error message.
     */
    protected function writeError(string $message): void
    {
        if (null !== $this->io) {
            $this->io->error($message);
        }
    }

    /**
     * Writes the given note message.
     */
    protected function writeNote(string $message): void
    {
        if (null !== $this->io) {
            $this->io->note($message);
        }
    }

    /**
     * Writes the given success message.
     */
    protected function writeSuccess(string $message): void
    {
        if (null !== $this->io) {
            $this->io->success($message);
        }
    }

    /**
     * Writes the given information message whether verbosity is verbose (-v).
     */
    protected function writeVerbose(string $message, string $style = ''): void
    {
        if ($this->isVerbose()) {
            $this->write($message, $style);
        }
    }

    /**
     * Writes the given information message whether verbosity is very verbose (-vv).
     */
    protected function writeVeryVerbose(string $message, string $style = ''): void
    {
        if ($this->isVeryVerbose()) {
            $this->write($message, $style);
        }
    }
}
