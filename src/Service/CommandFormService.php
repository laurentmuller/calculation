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
 * @phpstan-import-type CommandType from CommandService
 * @phpstan-import-type InputType from CommandService
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
     * @phpstan-param CommandType $command
     *
     * @phpstan-return FormInterface<mixed>
     */
    public function createForm(array $command, array $data, array $options = []): FormInterface
    {
        $builder = $this->factory->createBuilder(data: $data, options: $options);
        $helper = new FormHelper($builder);

        $transformer = $this->createDataTransformer();
        $this->addArguments($helper, $command['arguments'], $transformer);
        $this->addOptions($helper, $command['options'], $transformer);

        return $helper->createForm();
    }

    /**
     * Filter the view children by the given priority.
     *
     * @param FormView $view     the view to get filtered children
     * @param int      $priority the priority
     *
     * @phpstan-param self::* $priority
     *
     * @return FormView[]
     */
    public function filter(FormView $view, int $priority): array
    {
        return \array_filter($view->children, fn (FormView $child): bool => $priority === $this->getPriority($child));
    }

    /**
     * @phpstan-param array<string, InputType> $inputs
     */
    private function addArguments(FormHelper $helper, array $inputs, CallbackTransformer $transformer): void
    {
        foreach ($inputs as $key => $input) {
            $field = CommandDataService::getArgumentKey($key);
            if ($this->isArgumentText($input)) {
                $this->addTextField(
                    helper: $helper,
                    priority: self::ARGUMENT_TEXT,
                    field: $field,
                    name: $input['name'],
                    description: $input['description'],
                    required: $input['isRequired'],
                    transformer: $input['isArray'] ? $transformer : null
                );
                continue;
            }

            if (\is_bool($input['default'])) {
                $this->addBoolField(
                    helper: $helper,
                    priority: self::ARGUMENT_BOOL,
                    field: $field,
                    name: $input['name'],
                    description: $input['description'],
                    required: $input['isRequired']
                );
            }
        }
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
     * @phpstan-param array<string, InputType> $inputs
     */
    private function addOptions(FormHelper $helper, array $inputs, CallbackTransformer $transformer): void
    {
        foreach ($inputs as $key => $input) {
            $field = CommandDataService::getOptionKey($key);
            if ($this->isOptionText($input)) {
                $this->addTextField(
                    helper: $helper,
                    priority: self::OPTION_TEXT,
                    field: $field,
                    name: $input['name'],
                    description: $input['description'],
                    required: $input['isRequired'],
                    transformer: $input['isArray'] ? $transformer : null
                );
                continue;
            }

            if (!$input['isAcceptValue'] || \is_bool($input['default'])) {
                $this->addBoolField(
                    helper: $helper,
                    priority: self::OPTION_BOOL,
                    field: $field,
                    name: $input['name'],
                    description: $input['description'],
                    required: $input['isRequired']
                );
            }
        }
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
        if ($transformer instanceof CallbackTransformer) {
            $required = false;
        }
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

    private function createDataTransformer(): CallbackTransformer
    {
        return new CallbackTransformer(
            /** @phpstan-param string[] $data */
            static fn (array $data): string => \implode(',', \array_filter($data)),
            static fn (?string $data): array => StringUtils::isString($data) ? \array_map(trim(...), \explode(',', $data)) : []
        );
    }

    private function getPriority(FormView $view): int
    {
        return (int) ($view->vars['priority'] ?? -1);
    }

    /**
     * @phpstan-return array<string, mixed>
     */
    private function getTooltipAttributes(string $title, string $content): array
    {
        return [
            'data-bs-title' => $title,
            'data-bs-content' => $content,
            'data-bs-html' => 'true',
            'data-bs-trigger' => 'hover',
            'data-bs-toggle' => 'popover',
            'data-bs-placement' => 'top',
        ];
    }

    /**
     * @phpstan-param InputType $input
     */
    private function isArgumentText(array $input): bool
    {
        if ($input['isArray']) {
            return true;
        }

        $default = $input['default'];
        if (\is_bool($default)) {
            return false;
        }

        return null === $default || \is_string($default);
    }

    /**
     * @phpstan-param InputType $input
     */
    private function isOptionText(array $input): bool
    {
        if ($input['isArray']) {
            return true;
        }

        return $input['isAcceptValue'] && !\is_bool($input['default']);
    }
}
