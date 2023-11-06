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
 * @psalm-type SimpleEditorActionType = array{
 *      title: string|false|null,
 *      group: string|null,
 *      icon: string|null,
 *      text: string|null,
 *      exec: string,
 *      parameter: string|null,
 *      state: string|null,
 *      enabled: string|null,
 *      class: string|null,
 *      attributes: array<string, string>,
 *      actions?: array}
 *
 * @extends AbstractType<HiddenType>
 */
class SimpleEditorType extends AbstractType
{
    /*
     * the shared default actions.
     */
    private static ?array $defaultActions = null;

    /**
     * Constructor.
     */
    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/data/simple_editor_actions.json')]
        private readonly string $actionsPath
    ) {
    }

    /**
     * @psalm-suppress MixedArrayAssignment
     * @psalm-suppress MixedPropertyTypeCoercion
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if ($options['required']) {
            $view->vars['attr']['class'] = $this->getWidgetClass($view);
        }
        $view->vars['groups'] = $this->getGroupedActions($options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => true,
            'actions' => $this->getDefaultActions(),
        ])->setAllowedTypes('actions', ['array', 'null']);
    }

    public function getParent(): ?string
    {
        return HiddenType::class;
    }

    /**
     * Gets the definition of the default actions.
     */
    private function getDefaultActions(): array
    {
        if (empty(self::$defaultActions)) {
            try {
                self::$defaultActions = FileUtils::decodeJson($this->actionsPath);
            } catch (\InvalidArgumentException) {
                self::$defaultActions = [];
            }
        }

        return self::$defaultActions;
    }

    /**
     * Filter, update and group actions.
     */
    private function getGroupedActions(array $options): array
    {
        /** @psalm-var array $existing */
        $existing = $options['actions'] ?? [];

        /** @psalm-var SimpleEditorActionType[] $actions */
        $actions = \array_filter($existing, static fn (array $action): bool => !empty($action['exec']) || !empty($action['actions']));
        $this->updateActions($actions);

        $groups = [];
        foreach ($actions as $action) {
            $groups[$action['group'] ?? 'default'][] = $action;
        }

        return $groups;
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
     * @psalm-param SimpleEditorActionType[] $actions
     *
     * @psalm-return SimpleEditorActionType[]
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
     * @psalm-param SimpleEditorActionType $action
     */
    private function updateClass(array &$action, string $class): self
    {
        if (!empty($action['actions'])) {
            $class .= ' dropdown-toggle';
        }
        if (!empty($action['class'])) {
            $class .= ' ' . $action['class'];
        }
        $action['attributes']['class'] = $class;

        return $this;
    }

    /**
     * @psalm-param SimpleEditorActionType $action
     */
    private function updateDropDown(array &$action): void
    {
        /** @psalm-var SimpleEditorActionType[] $actions */
        $actions = $action['actions'] ?? [];
        if ([] !== $actions) {
            $action['attributes']['aria-expanded'] = 'false';
            $action['attributes']['data-bs-toggle'] = 'dropdown';
            $action['actions'] = $this->updateActions($actions, 'dropdown-item');
        }
    }

    /**
     * @psalm-param SimpleEditorActionType $action
     */
    private function updateEnabled(array &$action): self
    {
        if (!empty($action['enabled'])) {
            $action['attributes']['data-enabled'] = $action['enabled'];
        }

        return $this;
    }

    /**
     * @psalm-param SimpleEditorActionType $action
     */
    private function updateExec(array &$action): self
    {
        if (!empty($action['exec'])) {
            $action['attributes']['data-exec'] = $action['exec'];
        }

        return $this;
    }

    /**
     * @psalm-param SimpleEditorActionType $action
     */
    private function updateIcon(array &$action): self
    {
        $action['icon'] ??= $action['exec'];

        return $this;
    }

    /**
     * @psalm-param SimpleEditorActionType $action
     */
    private function updateParameter(array &$action): self
    {
        if (!empty($action['parameter'])) {
            $action['attributes']['data-parameter'] = $action['parameter'];
        }

        return $this;
    }

    /**
     * @psalm-param SimpleEditorActionType $action
     */
    private function updateState(array &$action): self
    {
        if (!empty($action['state'])) {
            $action['attributes']['data-state'] = $action['state'];
        }

        return $this;
    }

    /**
     * @psalm-param SimpleEditorActionType $action
     */
    private function updateText(array &$action): self
    {
        if (!empty($action['text'])) {
            $action['text'] = 'simple_editor.' . $action['text'];
        }

        return $this;
    }

    /**
     * @psalm-param SimpleEditorActionType $action
     */
    private function updateTitle(array &$action): self
    {
        $title = $action['title'] ?? $action['exec'];
        if (\is_string($title)) {
            $action['attributes']['title'] = "simple_editor.$title";
        }

        return $this;
    }
}
