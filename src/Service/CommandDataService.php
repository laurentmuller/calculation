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

/**
 * Service to generate and validate command parameters.
 *
 * @psalm-import-type CommandType from CommandService
 * @psalm-import-type ArgumentType from CommandService
 * @psalm-import-type OptionType from CommandService
 */
class CommandDataService
{
    /**
     * The argument's field prefix.
     */
    private const ARGUMENT_PREFIX = 'argument-';

    /**
     * The option's field prefix.
     */
    private const OPTION_PREFIX = 'option-';

    /**
     * Create the model (names and default values) for the given command.
     *
     * @psalm-param CommandType $command
     *
     * @psalm-return array<string, array|scalar|null>
     *
     * @phpstan-param array $command
     */
    public function createData(array $command): array
    {
        $data = [];

        foreach ($command['definition']['arguments'] as $key => $argument) {
            $name = self::getArgumentKey($key);
            $data[$name] = $argument['default'];
        }

        foreach ($command['definition']['options'] as $key => $option) {
            $name = self::getOptionKey($key);
            $data[$name] = $option['default'];
        }

        return $data;
    }

    /**
     * Create the command parameters from the given command and data.
     *
     * @psalm-param CommandType                      $command
     * @psalm-param array<string, array|scalar|null> $data
     *
     * @psalm-return array<string, array|scalar|null>
     *
     * @phpstan-param array $command
     */
    public function createParameters(array $command, array $data): array
    {
        $parameters = [];
        foreach ($data as $key => $value) {
            if (null === $value || false === $value || [] === $value) {
                continue;
            }
            if ($this->isArgumentPrefix($key)) {
                $key = $this->getArgumentName($command, $key);
            } elseif ($this->isOptionPrefix($key)) {
                $key = $this->getOptionName($command, $key);
            }
            $parameters[$key] = $value;
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
     * @psalm-param CommandType $command
     * @psalm-param array<string, array|scalar|null> $data
     *
     * @psalm-return array<string, array|scalar|null>
     *
     * @phpstan-param array $command
     */
    public function validateData(array $command, array $data): array
    {
        $arguments = $command['definition']['arguments'];
        $options = $command['definition']['options'];

        return \array_filter($data, function (string $key) use ($arguments, $options): bool {
            if ($this->isArgumentPrefix($key)) {
                return \array_key_exists($this->trimArgumentPrefix($key), $arguments);
            }
            if ($this->isOptionPrefix($key)) {
                return \array_key_exists($this->trimOptionPrefix($key), $options);
            }

            return false;
        }, \ARRAY_FILTER_USE_KEY);
    }

    /**
     * @psalm-param CommandType $command
     *
     * @phpstan-param array $command
     */
    private function getArgumentName(array $command, string $key): string
    {
        $key = $this->trimArgumentPrefix($key);
        $arguments = $command['definition']['arguments'];
        if (\array_key_exists($key, $arguments)) {
            return $arguments[$key]['name'];
        }

        return $key;
    }

    /**
     * @psalm-param CommandType $command
     *
     * @phpstan-param array $command
     */
    private function getOptionName(array $command, string $key): string
    {
        $key = $this->trimOptionPrefix($key);
        $options = $command['definition']['options'];
        if (\array_key_exists($key, $options)) {
            return $options[$key]['name'];
        }

        return '--' . $key;
    }

    private function isArgumentPrefix(string $key): bool
    {
        return \str_starts_with($key, self::ARGUMENT_PREFIX);
    }

    private function isOptionPrefix(string $key): bool
    {
        return \str_starts_with($key, self::OPTION_PREFIX);
    }

    private function trimArgumentPrefix(string $key): string
    {
        return \substr($key, \strlen(self::ARGUMENT_PREFIX));
    }

    private function trimOptionPrefix(string $key): string
    {
        return \substr($key, \strlen(self::OPTION_PREFIX));
    }
}
