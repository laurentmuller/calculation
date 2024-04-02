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
    private InputInterface $input;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
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
     * Returns the option value, as bool, for a given option name.
     *
     * @throws InvalidArgumentException When option given doesn't exist
     */
    public function getBoolOption(string $name): bool
    {
        return (bool) $this->getOption($name);
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
