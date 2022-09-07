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

use App\Util\Utils;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Trait to write output messages.
 */
trait LoggerTrait
{
    /**
     * The installer name.
     */
    protected ?string $installerName = null;

    /**
     * The symfony style.
     */
    protected ?SymfonyStyle $io = null;

    /**
     * Concat this installer name and the message.
     *
     * @param string $message the message to output
     *
     * @return string the concat message
     *
     * @throws \ReflectionException
     */
    protected function concat(string $message): string
    {
        return $this->getInstallerName() . ': ' . $message;
    }

    /**
     * Gets the installer name.
     *
     * @return string the installer name
     *
     * @throws \ReflectionException
     */
    protected function getInstallerName(): string
    {
        if (null === $this->installerName) {
            $this->installerName = Utils::getShortName(static::class);
        }

        return $this->installerName;
    }

    /**
     * Returns whether verbosity is verbose (-v).
     *
     * @return bool true if verbosity is set to VERBOSITY_VERBOSE, false otherwise
     */
    protected function isVerbose(): bool
    {
        return $this->io && $this->io->isVerbose();
    }

    /**
     * Returns whether verbosity is very verbose (-vv).
     *
     * @return bool true if verbosity is set to VERBOSITY_VERY_VERBOSE, false otherwise
     */
    protected function isVeryVerbose(): bool
    {
        return $this->io && $this->io->isVeryVerbose();
    }

    /**
     * Writes the given message.
     *
     * @param string $message the message to write
     *
     * @throws \ReflectionException
     */
    protected function write(string $message): void
    {
        if (null !== $this->io) {
            $concat = $this->concat($message);
            $this->io->writeln("<info>$concat</info>");
        }
    }

    /**
     * Writes the given error message.
     *
     * @param string $message the message to write
     *
     * @throws \ReflectionException
     */
    protected function writeError(string $message): void
    {
        if (null !== $this->io) {
            $concat = $this->concat($message);
            $this->io->error($concat);
        }
    }

    /**
     * Writes the given error message.
     *
     * @param string $message the message to write
     *
     * @throws \ReflectionException
     */
    protected function writeNote(string $message): void
    {
        if (null !== $this->io) {
            $concat = $this->concat($message);
            $this->io->note($concat);
        }
    }

    /**
     * Writes the given success message.
     *
     * @param string $message the message to write
     *
     * @throws \ReflectionException
     */
    protected function writeSuccess(string $message): void
    {
        if (null !== $this->io) {
            $concat = $this->concat($message);
            $this->io->success($concat);
        }
    }

    /**
     * Writes the given message.
     *
     * @param string $message the message to write with information style
     *
     * @throws \ReflectionException
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
     *
     * @throws \ReflectionException
     */
    protected function writeVeryVerbose(string $message): void
    {
        if ($this->isVeryVerbose()) {
            $this->write($message);
        }
    }
}
