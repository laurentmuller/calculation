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
use Symfony\Component\Form\FormView;

/**
 * Service to build form for a command.
 *
 * @psalm-import-type CommandType from CommandService
 * @psalm-import-type ArgumentType from CommandService
 * @psalm-import-type OptionType from CommandService
 */
readonly class CommandFormService
{
    /**
     * The argument's boolean field priority.
     */
    public const ARGUMENT_BOOL = 2;

    /**
     * The argument's text field priority.
     */
    public const ARGUMENT_TEXT = 1;

    /**
     * The option's boolean field priority.
     */
    public const OPTION_BOOL = 4;

    /**
     * The option's text field priority.
     */
    public const OPTION_TEXT = 3;

    public function __construct(private FormFactoryInterface $factory)
    {
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

        $transformer = $this->createDataTransformer();
        $this->addArguments($helper, $command, $transformer);
        $this->addOptions($helper, $command, $transformer);

        return $helper->createForm();
    }

    /**
     * Filter the view's children by the given priority.
     *
     * @param FormView $view     the view to get filtered children
     * @param int      $priority the priority
     *
     * @return FormView[]
     *
     * @psalm-param self::* $priority
     */
    public function filter(FormView $view, int $priority): array
    {
        return \array_filter($view->children, fn (FormView $child): bool => $priority === $this->getPriority($child));
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
    private function addBoolArgument(
        FormHelper $helper,
        string $key,
        array $argument
    ): void {
        $field = CommandDataService::getArgumentKey($key);
        $this->addBoolField(
            $helper,
            self::ARGUMENT_BOOL,
            $field,
            $argument['name'],
            $argument['description'],
            $argument['is_required']
        );
    }

    private function addBoolField(
        FormHelper $helper,
        int $priority,
        string $field,
        string $name,
        string $description,
        bool $required,
    ): void {
        $attributes = $this->getTooltipAttributes($name, $description);
        $helper->field($field)
            ->label($name)
            ->rowClass('col-6 col-md-3')
            ->updateRowAttributes($attributes)
            ->updateOption('priority', $priority)
            ->required($required)
            ->domain(false)
            ->addCheckboxType(switch: false);
    }

    /**
     * @psalm-param OptionType $option
     */
    private function addBoolOption(
        FormHelper $helper,
        string $key,
        array $option
    ): void {
        $field = CommandDataService::getOptionKey($key);
        $this->addBoolField(
            $helper,
            self::OPTION_BOOL,
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

    /**
     * @psalm-param ArgumentType $argument
     */
    private function addTextArgument(
        FormHelper $helper,
        string $key,
        array $argument,
        ?CallbackTransformer $transformer = null
    ): void {
        $field = CommandDataService::getArgumentKey($key);
        $this->addTextField(
            $helper,
            self::ARGUMENT_TEXT,
            $field,
            $argument['name'],
            $argument['description'],
            $argument['is_required'],
            $transformer
        );
    }

    private function addTextField(
        FormHelper $helper,
        int $priority,
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
            ->rowClass('col-6 col-md-3')
            ->updateAttributes($attributes)
            ->updateOption('priority', $priority)
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
        $field = CommandDataService::getOptionKey($key);
        $this->addTextField(
            $helper,
            self::OPTION_TEXT,
            $field,
            $option['name'],
            $option['description'],
            $option['is_value_required'],
            $transformer
        );
    }

    private function createDataTransformer(): CallbackTransformer
    {
        return new CallbackTransformer(
            /** @psalm-param string[] $data */
            fn (array $data) => \implode(',', \array_filter($data)),
            fn (?string $data) => StringUtils::isString($data) ? \explode(',', $data) : []
        );
    }

    private function getPriority(FormView $view): int
    {
        return (int) ($view->vars['priority'] ?? -1);
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
}
