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
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if ($options['required']) {
            $view->vars['attr']['class'] = $this->getWidgetClass($view);
        }
        $view->vars['actions'] = $this->getActions($options);
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
    public function getParent()
    {
        return HiddenType::class;
    }

    /**
     * Filter and update actions.
     */
    private function getActions(array $options): array
    {
        $actions = \array_filter($options['actions'] ?? [], static function (array $action): bool {
            return !empty($action['exec']);
        });

        foreach ($actions as &$action) {
            $action['title'] = 'simple_editor.' . ($action['title'] ?? $action['exec']);
            $action['icon'] ??= $action['exec'];
        }

        return $actions;
    }

    /**
     * Gets the definition of the default actions.
     */
    private function getDefaultActions(): array
    {
        $file = __DIR__ . '/simple_editor_actions.json';

        try {
            return FileUtils::decodeJson($file);
        } catch (\InvalidArgumentException $e) {
            return [];
        }
    }

    /**
     * Gets the class name when the required option is set.
     */
    private function getWidgetClass(FormView $view): string
    {
        $values = \array_filter(\explode(' ', $view->vars['attr']['class'] ?? ''));
        $values[] = 'must-validate';

        return \implode(' ', \array_unique($values));
    }
}
