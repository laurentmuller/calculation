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

namespace App\Form\Type;

use App\Traits\GroupByTrait;
use App\Utils\FileUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The Simple editor type.
 *
 * @psalm-type ActionType = array{
 *      title?: string|false,
 *      group?: string,
 *      icon?: string,
 *      text?: string,
 *      exec?: string,
 *      parameter?: string,
 *      state?: string,
 *      enabled?: string,
 *      class?: string,
 *      attributes: array<string, string>,
 *      actions?: array}
 *
 * @extends AbstractType<HiddenType>
 */
class SimpleEditorType extends AbstractType
{
    use GroupByTrait;

    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/data/simple_editor_actions.json')]
        private readonly string $actionsPath
    ) {
    }

    /**
     * @psalm-param array{required: bool, ...} $options
     */
    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if ($options['required']) {
            $view->vars['attr'] = \array_merge($view->vars['attr'], ['class' => $this->getWidgetClass($view)]);
        }
        $view->vars['groups'] = $this->getGroupedActions($options);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => true,
            'actions' => $this->getDefaultActions(),
        ])->setAllowedTypes('actions', ['array', 'null']);
    }

    #[\Override]
    public function getParent(): string
    {
        return HiddenType::class;
    }

    /**
     * @psalm-param ActionType $action
     */
    private function filterAction(array $action): bool
    {
        return ($action['exec'] ?? '') !== '' || $this->isActions($action);
    }

    /**
     * Gets the definition of the default actions.
     *
     * @psalm-return ActionType[]
     */
    private function getDefaultActions(): array
    {
        try {
            /** @psalm-var ActionType[]  */
            return FileUtils::decodeJson($this->actionsPath);
        } catch (\InvalidArgumentException) {
            return [];
        }
    }

    /**
     * Filters, updates, and groups actions.
     *
     * @psalm-param array{actions?: array, ...} $options
     */
    private function getGroupedActions(array $options): array
    {
        if (!$this->isActions($options)) {
            return [];
        }

        /** @psalm-var ActionType[] $actions */
        $actions = $options['actions'] ?? [];
        $actions = \array_filter(
            $actions,
            /** @psalm-param ActionType $action */
            fn (array $action): bool => $this->filterAction($action)
        );
        $this->updateActions($actions);

        return $this->groupBy($actions, static fn (array $action): string => $action['group'] ?? 'default');
    }

    /**
     * Gets the class name when the required option is set.
     */
    private function getWidgetClass(FormView $view): string
    {
        /** @psalm-var string $class */
        $class = $view->vars['attr']['class'] ?? '';
        $values = \array_filter(\explode(' ', $class));
        $values[] = 'must-validate';

        return \implode(' ', \array_unique($values));
    }

    /**
     * @psalm-param array{actions?: array, ...} $action
     *
     * @psalm-assert-if-true ActionType[] $action['actions']
     */
    private function isActions(array $action): bool
    {
        return isset($action['actions']) && [] !== $action['actions'];
    }

    /**
     * @psalm-param ActionType[] $actions
     *
     * @psalm-return ActionType[]
     */
    private function updateActions(array &$actions, string $class = 'btn btn-outline-secondary'): array
    {
        foreach ($actions as &$action) {
            $this->updateClass($action, $class)
                ->updateIcon($action)
                ->updateTitle($action)
                ->updateExec($action)
                ->updateText($action)
                ->updateState($action)
                ->updateEnabled($action)
                ->updateParameter($action)
                ->updateDropDown($action);
        }

        return $actions;
    }

    /**
     * @psalm-param ActionType $action
     */
    private function updateAttribute(array &$action, string $key): self
    {
        if (isset($action[$key]) && \is_string($action[$key])) {
            $action['attributes']['data-' . $key] = $action[$key];
        }

        return $this;
    }

    /**
     * @psalm-param ActionType $action
     */
    private function updateClass(array &$action, string $class): self
    {
        if ($this->isActions($action)) {
            $class .= ' dropdown-toggle';
        }
        if (isset($action['class'])) {
            $class .= ' ' . $action['class'];
        }
        $action['attributes']['class'] = \trim($class);

        return $this;
    }

    /**
     * @psalm-param ActionType $action
     */
    private function updateDropDown(array &$action): void
    {
        if (!$this->isActions($action)) {
            return;
        }

        $actions = $action['actions'] ?? [];
        $action['attributes']['aria-expanded'] = 'false';
        $action['attributes']['data-bs-toggle'] = 'dropdown';
        $action['actions'] = $this->updateActions($actions, 'dropdown-item'); // @phpstan-ignore-line
    }

    /**
     * @psalm-param ActionType $action
     */
    private function updateEnabled(array &$action): self
    {
        return $this->updateAttribute($action, 'enabled');
    }

    /**
     * @psalm-param ActionType $action
     */
    private function updateExec(array &$action): self
    {
        return $this->updateAttribute($action, 'exec');
    }

    /**
     * @psalm-param ActionType $action
     */
    private function updateIcon(array &$action): self
    {
        if (!isset($action['icon']) && isset($action['exec'])) {
            $action['icon'] = $action['exec'];
        }

        return $this;
    }

    /**
     * @psalm-param ActionType $action
     */
    private function updateParameter(array &$action): self
    {
        return $this->updateAttribute($action, 'parameter');
    }

    /**
     * @psalm-param ActionType $action
     */
    private function updateState(array &$action): self
    {
        return $this->updateAttribute($action, 'state');
    }

    /**
     * @psalm-param ActionType $action
     */
    private function updateText(array &$action): self
    {
        if (isset($action['text'])) {
            $action['text'] = 'simple_editor.' . $action['text'];
        }

        return $this;
    }

    /**
     * @psalm-param ActionType $action
     */
    private function updateTitle(array &$action): self
    {
        $title = $action['title'] ?? $action['exec'] ?? null;
        if (\is_string($title)) {
            $action['attributes']['title'] = "simple_editor.$title";
        }

        return $this;
    }
}
