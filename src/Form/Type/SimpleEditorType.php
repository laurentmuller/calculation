<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Form\Type;

use App\Util\FileUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The Simple editor type.
 *
 * @author Laurent Muller
 */
class SimpleEditorType extends AbstractType
{
    /**
     * {@inheritdoc}
     *
     * @param array{
     *      required:bool
     * } $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /**
         * @var array{
         *      attr: array,
         *      groups: array
         * } $vars
         */
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
        $file = __DIR__ . '/simple_editor_actions.json';

        try {
            return (array) FileUtils::decodeJson($file);
        } catch (\InvalidArgumentException $e) {
            return [];
        }
    }

    /**
     * Filter, update and group actions.
     */
    private function getGroupedActions(array $options): array
    {
        /** @var array $exisiting */
        $exisiting = $options['actions'] ?? [];

        /**
         * @var array<array{
         *      title: null|string,
         *      group: string,
         *      icon: null|string,
         *      text: string,
         *      exec: string,
         *      parameter: string,
         *      state: string,
         *      enabled: string,
         *      class: string,
         *      attributes: array<string, string>,
         *      actions: array}> $actions
         */
        $actions = \array_filter($exisiting, static function (array $action): bool {
            return !empty($action['exec']) || !empty($action['actions']);
        });
        $this->updateActions($actions);

        $groups = [];
        foreach ($actions as $action) {
            $groups[$action['group']][] = $action;
        }

        return $groups;
    }

    /**
     * Gets the class name when the required option is set.
     */
    private function getWidgetClass(FormView $view): string
    {
        /** @var string $class */
        $class = $view->vars['attr']['class'] ?? '';
        $values = \array_filter(\explode(' ', $class));
        $values[] = 'must-validate';

        return \implode(' ', \array_unique($values));
    }

    /**
     * @param array<array{
     *      title: null|string,
     *      group: string,
     *      icon: null|string,
     *      text: string,
     *      exec: string,
     *      parameter: string,
     *      state: string,
     *      enabled: string,
     *      class: string,
     *      attributes: array<string, string>,
     *      actions: null|array}> $actions
     *
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    private function updateActions(array &$actions, string $class = 'btn btn-outline-secondary'): void
    {
        foreach ($actions as &$action) {
            if (empty($action['group'])) {
                $action['group'] = 'default';
            }
            $action['icon'] ??= $action['exec'];
            $action['attributes']['class'] = $class;
            $action['attributes']['title'] = 'simple_editor.' . ($action['title'] ?? $action['exec']);

            if (!empty($action['text'])) {
                $action['text'] = 'simple_editor.' . $action['text'];
                unset($action['icon']);
            }
            if (!empty($action['exec'])) {
                $action['attributes']['data-exec'] = $action['exec'];
                unset($action['exec']);
            }
            if (!empty($action['parameter'])) {
                $action['attributes']['data-parameter'] = $action['parameter'];
                unset($action['parameter']);
            }
            if (!empty($action['state'])) {
                $action['attributes']['data-state'] = $action['state'];
                unset($action['state']);
            }
            if (!empty($action['enabled'])) {
                $action['attributes']['data-enabled'] = $action['enabled'];
                unset($action['enabled']);
            }
            if (!empty($action['class'])) {
                $action['attributes']['class'] .= ' ' . $action['class'];
            }
            unset($action['class']);

            // drop-down items?
            if (isset($action['actions'])) {
                $action['attributes']['aria-expanded'] = 'false';
                $action['attributes']['data-toggle'] = 'dropdown';
                $action['attributes']['class'] .= ' dropdown-toggle';
            }
            if (!empty($action['actions'])) {
                $action['attributes']['aria-expanded'] = 'false';
                $action['attributes']['data-toggle'] = 'dropdown';
                $action['attributes']['class'] .= ' dropdown-toggle';
                $this->updateActions($action['actions'], 'dropdown-item');
            }
        }
    }
}
