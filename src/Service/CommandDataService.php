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

namespace App\Service;

use App\Utils\StringUtils;

/**
 * Service to generate and validate command parameters.
 *
 * @phpstan-import-type CommandType from CommandService
 * @phpstan-import-type InputType from CommandService
 */
class CommandDataService
{
    /**
     * The argument field prefix.
     */
    private const ARGUMENT_PREFIX = 'argument-';

    /**
     * The option field prefix.
     */
    private const OPTION_PREFIX = 'option-';

    /**
     * Create the model (names and default values) for the given command.
     *
     * @phpstan-param CommandType $command
     *
     * @phpstan-return array<string, array|scalar|null>
     */
    public function createData(array $command): array
    {
        $data = [];

        foreach ($command['arguments'] as $key => $argument) {
            $name = self::getArgumentKey($key);
            $data[$name] = $argument['default'];
        }

        foreach ($command['options'] as $key => $option) {
            $name = self::getOptionKey($key);
            $data[$name] = $option['default'];
        }

        return $data;
    }

    /**
     * Create the command parameters from the given command and data.
     *
     * @phpstan-param CommandType $command
     * @phpstan-param array<string, array|scalar|null> $data
     *
     * @phpstan-return array<string, array|scalar|null>
     *
     * @throws \LogicException if a parameter name is not found
     */
    public function createParameters(array $command, array $data): array
    {
        $parameters = [];
        foreach ($data as $key => $value) {
            $default = $this->getDefaultValue($command, $key);
            if ($default === $value || (null === $default && false === $value)) {
                continue;
            }

            if ($this->isArgumentPrefix($key)) {
                $key = $this->getArgumentName($command, $key);
                $parameters[$key] = $value;
                continue;
            }

            if ($this->isOptionPrefix($key)) {
                $key = $this->getOptionName($command, $key);
                $parameters[$key] = $value;
                continue;
            }

            throw new \LogicException(\sprintf("Unable to find the argument '%s'.", $key));
        }

        return $parameters;
    }

    /**
     * Gets the argument's data key by adding the argument prefix to the given name.
     */
    public static function getArgumentKey(string $name): string
    {
        return self::ARGUMENT_PREFIX . $name;
    }

    /**
     * Gets the option's data key by adding the option prefix to the given name.
     */
    public static function getOptionKey(string $name): string
    {
        return self::OPTION_PREFIX . $name;
    }

    /**
     * Validate the model (names) for the given command.
     *
     * @phpstan-param CommandType $command
     * @phpstan-param array<string, mixed> $data
     */
    public function validateData(array $command, array $data): array
    {
        return \array_filter($data, fn (string $key): bool => $this->validateKey($key, $command), \ARRAY_FILTER_USE_KEY);
    }

    /**
     * @phpstan-param CommandType $command
     *
     * @throws \LogicException if the argument name is not found
     */
    private function getArgumentName(array $command, string $key): string
    {
        $key = $this->trimArgumentPrefix($key);
        $arguments = $command['arguments'];
        if (!\array_key_exists($key, $arguments)) {
            throw new \LogicException(\sprintf("Unable to find the argument '%s'.", $key));
        }

        return $arguments[$key]['name'];
    }

    /**
     * @phpstan-param CommandType $command
     *
     * @phpstan-return array|scalar|null
     */
    private function getDefaultValue(array $command, string $key): mixed
    {
        if ($this->isArgumentPrefix($key)) {
            $key = $this->trimArgumentPrefix($key);
            if (\array_key_exists($key, $command['arguments'])) {
                return $command['arguments'][$key]['default'];
            }
        }

        if ($this->isOptionPrefix($key)) {
            $key = $this->trimOptionPrefix($key);
            if (\array_key_exists($key, $command['options'])) {
                return $command['options'][$key]['default'];
            }
        }

        return null;
    }

    /**
     * @phpstan-param CommandType $command
     *
     * @throws \LogicException if the option name is not found
     */
    private function getOptionName(array $command, string $key): string
    {
        $key = $this->trimOptionPrefix($key);
        $options = $command['options'];
        if (!\array_key_exists($key, $options)) {
            throw new \LogicException(\sprintf("Unable to find the option '%s'.", $key));
        }

        return $options[$key]['name'];
    }

    private function isArgumentPrefix(string $key): bool
    {
        return StringUtils::startWith($key, self::ARGUMENT_PREFIX);
    }

    private function isOptionPrefix(string $key): bool
    {
        return StringUtils::startWith($key, self::OPTION_PREFIX);
    }

    private function trimArgumentPrefix(string $key): string
    {
        return \substr($key, \strlen(self::ARGUMENT_PREFIX));
    }

    private function trimOptionPrefix(string $key): string
    {
        return \substr($key, \strlen(self::OPTION_PREFIX));
    }

    /**
     * @phpstan-param CommandType $command
     */
    private function validateKey(string $key, array $command): bool
    {
        if ($this->isArgumentPrefix($key)) {
            return \array_key_exists($this->trimArgumentPrefix($key), $command['arguments']);
        }

        if ($this->isOptionPrefix($key)) {
            return \array_key_exists($this->trimOptionPrefix($key), $command['options']);
        }

        return false;
    }
}
