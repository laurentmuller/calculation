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
 * Service to build form and parameters for a command.
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

        $arguments = $this->getSortedArguments($command);
        foreach ($arguments as $key => $argument) {
            $name = $this->addPrefix($key, self::ARGUMENT_PREFIX);
            $data[$name] = $argument['default'];
        }

        $options = $this->getSortedOptions($command);
        foreach ($options as $key => $option) {
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
     * @psalm-param CommandType $command
     *
     * @phpstan-param array $command
     */
    private function addArguments(FormHelper $helper, array $command, CallbackTransformer $transformer): void
    {
        foreach ($command['definition']['arguments'] as $key => $argument) {
            if ($this->isArgumentText($argument)) {
                if ($argument['is_array']) {
                    $this->addTextArgument($helper, $key, $argument, $transformer);
                } else {
                    $this->addTextArgument($helper, $key, $argument);
                }
                continue;
            }

            if (\is_bool($argument['default'])) {
                $this->addBoolArgument($helper, $key, $argument);
            }
        }
    }

    /**
     * @psalm-param ArgumentType $argument
     */
    private function addBoolArgument(FormHelper $helper, string $key, array $argument): void
    {
        $field = $this->addPrefix($key, self::ARGUMENT_PREFIX);
        $this->addBoolField(
            $helper,
            $field,
            $argument['name'],
            $argument['description'],
            $argument['is_required']
        );
    }

    private function addBoolField(
        FormHelper $helper,
        string $field,
        string $name,
        string $description,
        bool $required,
    ): void {
        $attributes = $this->getTooltipAttributes($name, $description);
        $helper->field($field)
            ->label($name)
            ->updateRowAttributes($attributes)
            ->required($required)
            ->domain(false)
            ->addCheckboxType(switch: false);
    }

    /**
     * @psalm-param OptionType $option
     */
    private function addBoolOption(FormHelper $helper, string $key, array $option): void
    {
        $field = $this->addPrefix($key, self::OPTION_PREFIX);
        $this->addBoolField(
            $helper,
            $field,
            $option['name'],
            $option['description'],
            $option['is_value_required']
        );
    }

    /**
     * @psalm-param CommandType $command
     *
     * @phpstan-param array $command
     */
    private function addOptions(FormHelper $helper, array $command, CallbackTransformer $transformer): void
    {
        foreach ($command['definition']['options'] as $key => $option) {
            if ($this->isOptionText($option)) {
                if ($option['is_multiple']) {
                    $this->addTextOption($helper, $key, $option, $transformer);
                } else {
                    $this->addTextOption($helper, $key, $option);
                }
                continue;
            }

            if (!$option['accept_value'] || \is_bool($option['default'])) {
                $this->addBoolOption($helper, $key, $option);
            }
        }
    }

    private function addPrefix(string $name, string $prefix): string
    {
        return $prefix . $name;
    }

    /**
     * @psalm-param ArgumentType $argument
     */
    private function addTextArgument(
        FormHelper $helper,
        string $key,
        array $argument,
        ?CallbackTransformer $transformer = null
    ): void {
        $field = $this->addPrefix($key, self::ARGUMENT_PREFIX);
        $this->addTextField(
            $helper,
            $field,
            $argument['name'],
            $argument['description'],
            $argument['is_required'],
            $transformer
        );
    }

    private function addTextField(
        FormHelper $helper,
        string $field,
        string $name,
        string $description,
        bool $required,
        ?CallbackTransformer $transformer = null,
    ): void {
        $attributes = $this->getTooltipAttributes($name, $description);
        $required = $transformer instanceof CallbackTransformer ? false : $required;
        $helper->field($field)
            ->label($name)
            ->labelClass('text-nowrap mb-1 w-50')
            ->rowClass('d-flex-no-wrap-center')
            ->updateRowAttributes($attributes)
            ->modelTransformer($transformer)
            ->required($required)
            ->domain(false)
            ->addTextType();
    }

    /**
     * @psalm-param OptionType $option
     */
    private function addTextOption(
        FormHelper $helper,
        string $key,
        array $option,
        ?CallbackTransformer $transformer = null
    ): void {
        $field = $this->addPrefix($key, self::OPTION_PREFIX);
        $this->addTextField(
            $helper,
            $field,
            $option['name'],
            $option['description'],
            $option['is_value_required'],
            $transformer
        );
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

    /**
     * Create a form for the given command.
     *
     * @psalm-param CommandType $command
     *
     * @psalm-return array<string, ArgumentType>
     *
     * @phpstan-param array $command
     */
    private function getSortedArguments(array $command): array
    {
        $arguments = $command['definition']['arguments'];
        \uasort(
            $arguments,
            /**
             * @psalm-param ArgumentType $a
             * @psalm-param ArgumentType $b
             */
            fn (array $a, array $b): int => (int) $this->isArgumentText($a) <=> (int) $this->isArgumentText($b)
        );

        return $arguments;
    }

    /**
     * Create a form for the given command.
     *
     * @psalm-param CommandType $command
     *
     * @psalm-return array<string, OptionType>
     *
     * @phpstan-param array $command
     */
    private function getSortedOptions(array $command): array
    {
        $options = $command['definition']['options'];
        \uasort(
            $options,
            /**
             * @psalm-param OptionType $a
             * @psalm-param OptionType $b
             */
            fn (array $a, array $b): int => (int) $this->isOptionText($a) <=> (int) $this->isOptionText($b)
        );

        return $options;
    }

    /**
     * @psalm-return array<string, mixed>
     */
    private function getTooltipAttributes(string $title, string $content): array
    {
        return [
            'data-bs-title' => $title,
            'data-bs-content' => $content,
            'data-bs-html' => 'true',
            'data-bs-trigger' => 'hover',
            'data-bs-toggle' => 'popover',
            'data-bs-placement' => 'left',
        ];
    }

    private function isArgumentPrefix(string $key): bool
    {
        return \str_starts_with($key, self::ARGUMENT_PREFIX);
    }

    /**
     * @psalm-param ArgumentType $argument
     *
     * @phpstan-param array $argument
     */
    private function isArgumentText(array $argument): bool
    {
        if ($argument['is_array']) {
            return true;
        }

        $default = $argument['default'];
        if (\is_bool($default)) {
            return false;
        }

        return null === $default || \is_string($default);
    }

    private function isOptionPrefix(string $key): bool
    {
        return \str_starts_with($key, self::OPTION_PREFIX);
    }

    /**
     * @psalm-param OptionType $option
     *
     * @phpstan-param array $option
     */
    private function isOptionText(array $option): bool
    {
        if ($option['is_multiple']) {
            return true;
        }

        return $option['accept_value'] && !\is_bool($option['default']);
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
