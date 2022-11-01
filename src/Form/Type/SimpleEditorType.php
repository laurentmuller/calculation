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

use App\Util\FileUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The Simple editor type.
 *
 * @psalm-type SimpleEditorAction = array{
 *      title: ?string,
 *      group: ?string,
 *      icon: ?string,
 *      text: string,
 *      exec: string,
 *      parameter: string,
 *      state: string,
 *      enabled: string,
 *      class: string,
 *      attributes: array<string, string>,
 *      actions: ?array}
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
     * {@inheritdoc}
     *
     * @psalm-param array{required:bool} $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var array{attr: array, groups: array} $vars */
        $vars = &$view->vars;
        if ($options['required']) {
            $vars['attr']['class'] = $this->getWidgetClass($view);
        }
        $view->vars['groups'] = $this->getGroupedActions($options);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => true,
            'actions' => $this->getDefaultActions(),
        ])->setAllowedTypes('actions', ['array', 'null']);
    }

    /**
     * {@inheritDoc}
     */
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
                $file = FileUtils::buildPath($this->actionsPath);
                self::$defaultActions = (array) FileUtils::decodeJson($file);
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

        /** @psalm-var SimpleEditorAction[] $actions */
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
     * @psalm-param SimpleEditorAction[] $actions
     */
    private function updateActions(array &$actions, string $class = 'btn btn-outline-secondary'): void
    {
        foreach ($actions as &$action) {
            $action['icon'] ??= $action['exec'];
            $action['attributes']['class'] = $class;
            $action['attributes']['title'] = 'simple_editor.' . ($action['title'] ?? $action['exec']);

            $this->updateExec($action)
                ->updateText($action)
                ->updateState($action)
                ->updateClass($action)
                ->updateEnabled($action)
                ->updateParameter($action)
                ->updateDropDown($action);
        }
    }

    /**
     * @psalm-param SimpleEditorAction $action
     */
    private function updateClass(array &$action): self
    {
        if (!empty($action['class'])) {
            $action['attributes']['class'] .= ' ' . $action['class'];
        }

        return $this;
    }

    /**
     * @psalm-param SimpleEditorAction $action
     */
    private function updateDropDown(array &$action): void
    {
        if (isset($action['actions']) && !empty($action['actions'])) {
            $action['attributes']['aria-expanded'] = 'false';
            $action['attributes']['data-toggle'] = 'dropdown';
            $action['attributes']['class'] .= ' dropdown-toggle';

            /** @psalm-var SimpleEditorAction[] $_children */
            $_children = &$action['actions'];
            $this->updateActions($_children, 'dropdown-item');
        }
    }

    /**
     * @psalm-param SimpleEditorAction $action
     */
    private function updateEnabled(array &$action): self
    {
        if (!empty($action['enabled'])) {
            $action['attributes']['data-enabled'] = $action['enabled'];
        }

        return $this;
    }

    /**
     * @psalm-param SimpleEditorAction $action
     */
    private function updateExec(array &$action): self
    {
        if (!empty($action['exec'])) {
            $action['attributes']['data-exec'] = $action['exec'];
        }

        return $this;
    }

    /**
     * @psalm-param SimpleEditorAction $action
     */
    private function updateParameter(array &$action): self
    {
        if (!empty($action['parameter'])) {
            $action['attributes']['data-parameter'] = $action['parameter'];
        }

        return $this;
    }

    /**
     * @psalm-param SimpleEditorAction $action
     */
    private function updateState(array &$action): self
    {
        if (!empty($action['state'])) {
            $action['attributes']['data-state'] = $action['state'];
        }

        return $this;
    }

    /**
     * @psalm-param SimpleEditorAction $action
     */
    private function updateText(array &$action): self
    {
        if (!empty($action['text'])) {
            $action['text'] = 'simple_editor.' . $action['text'];
        }

        return $this;
    }
}
