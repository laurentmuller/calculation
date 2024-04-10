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

use App\Form\FormHelper;
use App\Utils\StringUtils;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Service to build form for a command.
 *
 * @psalm-import-type CommandType from CommandService
 * @psalm-import-type ArgumentType from CommandService
 * @psalm-import-type OptionType from CommandService
 */
readonly class CommandBuilderService
{
    /**
     * The argument form prefix.
     */
    public const ARGUMENT_PREFIX = 'argument-';

    /**
     * The option form prefix.
     */
    public const OPTION_PREFIX = 'option-';

    public function __construct(private FormFactoryInterface $factory)
    {
    }

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
            $name = $this->addPrefix($key, self::ARGUMENT_PREFIX);
            $data[$name] = $argument['default'];
        }
        foreach ($command['definition']['options'] as $key => $option) {
            $name = $this->addPrefix($key, self::OPTION_PREFIX);
            $data[$name] = $option['default'];
        }

        return $data;
    }

    /**
     * Create a form for the given command.
     *
     * @psalm-param CommandType $command
     * @psalm-param array<string, array|scalar|null> $data
     *
     * @phpstan-param array $command
     */
    public function createForm(array $command, array $data): FormInterface
    {
        $builder = $this->factory->createBuilder(data: $data);
        $helper = new FormHelper($builder);

        $transformer = $this->createDataTransformerInterface();
        $this->addArguments($helper, $command, $transformer);
        $this->addOptions($helper, $command, $transformer);

        return $helper->createForm();
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
     * @psalm-param ArgumentType $argument
     */
    private function addArgumentBool(FormHelper $helper, string $key, array $argument): void
    {
        $field = $this->addPrefix($key, self::ARGUMENT_PREFIX);
        $helper->field($field)
            ->label($argument['name'])
            ->help($argument['description'])
            ->required($argument['is_required'])
            ->domain(false)
            ->helpHtml()
            ->addCheckboxType(switch: false);
    }

    /**
     * @psalm-param CommandType $command
     *
     * @phpstan-param array $command
     */
    private function addArguments(FormHelper $helper, array $command, CallbackTransformer $transformer): void
    {
        foreach ($command['definition']['arguments'] as $key => $argument) {
            if ($argument['is_array']) {
                $this->addArgumentText($helper, $key, $argument, $transformer);
                continue;
            }

            $default = $argument['default'];
            if (\is_bool($default)) {
                $this->addArgumentBool($helper, $key, $argument);
                continue;
            }

            if (null === $default || \is_string($default)) {
                $this->addArgumentText($helper, $key, $argument);
            }
        }
    }

    /**
     * @psalm-param ArgumentType $argument
     */
    private function addArgumentText(
        FormHelper $helper,
        string $key,
        array $argument,
        ?CallbackTransformer $transformer = null
    ): void {
        $field = $this->addPrefix($key, self::ARGUMENT_PREFIX);
        $required = $transformer instanceof CallbackTransformer ? false : $argument['is_required'];
        $helper->field($field)
            ->label($argument['name'])
            ->help($argument['description'])
            ->modelTransformer($transformer)
            ->required($required)
            ->domain(false)
            ->helpHtml()
            ->addTextType();
    }

    /**
     * @psalm-param OptionType $option
     */
    private function addOptionBool(FormHelper $helper, string $key, array $option): void
    {
        $field = $this->addPrefix($key, self::OPTION_PREFIX);
        $helper->field($field)
            ->label($option['name'])
            ->help($option['description'])
            ->required($option['is_value_required'])
            ->domain(false)
            ->helpHtml()
            ->addCheckboxType(switch: false);
    }

    /**
     * @psalm-param CommandType $command
     *
     * @phpstan-param array $command
     */
    private function addOptions(FormHelper $helper, array $command, CallbackTransformer $transformer): void
    {
        foreach ($command['definition']['options'] as $key => $option) {
            if ($option['is_multiple']) {
                $this->addOptionText($helper, $key, $option, $transformer);
                continue;
            }

            if (!$option['accept_value'] || \is_bool($option['default'])) {
                $this->addOptionBool($helper, $key, $option);
                continue;
            }

            $this->addOptionText($helper, $key, $option);
        }
    }

    /**
     * @psalm-param OptionType $option
     */
    private function addOptionText(
        FormHelper $helper,
        string $key,
        array $option,
        ?CallbackTransformer $transformer = null
    ): void {
        $field = $this->addPrefix($key, self::OPTION_PREFIX);
        $required = $transformer instanceof CallbackTransformer ? false : $option['is_value_required'];
        $helper->field($field)
            ->label($option['name'])
            ->help($option['description'])
            ->modelTransformer($transformer)
            ->required($required)
            ->domain(false)
            ->helpHtml()
            ->addTextType();
    }

    private function addPrefix(string $name, string $prefix): string
    {
        return $prefix . $name;
    }

    private function createDataTransformerInterface(): CallbackTransformer
    {
        return new CallbackTransformer(
            /** @psalm-param string[] $data */
            fn (array $data) => \implode(',', \array_filter($data)),
            fn (?string $data) => StringUtils::isString($data) ? \explode(',', $data) : []
        );
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
