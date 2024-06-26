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

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle as BaseSymfonyStyle;

/**
 * Extends the symfony style.
 */
class SymfonyStyle extends BaseSymfonyStyle
{
    public function __construct(
        private readonly InputInterface $input,
        private readonly OutputInterface $output
    ) {
        parent::__construct($input, $output);
    }

    /**
     * Gets the formatted duration for the given start time.
     *
     * @param int $startTime the start time
     */
    public function formatDuration(int $startTime): string
    {
        return Helper::formatTime(\time() - $startTime);
    }

    /**
     * Returns the argument value for a given argument name.
     *
     * @throws InvalidArgumentException When argument given doesn't exist
     */
    public function getArgument(string $name): mixed
    {
        return $this->input->getArgument($name);
    }

    /**
     * Returns the option value, as an array of strings, for a given option name.
     *
     * @return string[]
     *
     * @throws InvalidArgumentException When option given doesn't exist
     */
    public function getArrayOption(string $name): array
    {
        /** @psalm-var string[] */
        return (array) $this->input->getOption($name);
    }

    /**
     * Returns the option value, as bool, for a given option name.
     *
     * @throws InvalidArgumentException When option given doesn't exist
     */
    public function getBoolOption(string $name): bool
    {
        return (bool) $this->getOption($name);
    }

    public function getInput(): InputInterface
    {
        return $this->input;
    }

    /**
     * Returns the option value, as integer, for a given option name.
     *
     * @throws InvalidArgumentException When option given doesn't exist
     */
    public function getIntOption(string $name): int
    {
        return (int) $this->getOption($name);
    }

    /**
     * Returns the option value for a given option name.
     *
     * @throws InvalidArgumentException When option given doesn't exist
     */
    public function getOption(string $name): mixed
    {
        return $this->input->getOption($name);
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * Returns the argument value, as string, for a given argument name.
     *
     * @throws InvalidArgumentException When argument given doesn't exist
     */
    public function getStringArgument(string $name): string
    {
        return (string) $this->getArgument($name);
    }

    /**
     * Is this input means interactive?
     */
    public function isInteractive(): bool
    {
        return $this->input->isInteractive();
    }
}
