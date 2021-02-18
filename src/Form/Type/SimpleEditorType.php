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
            $view->vars['attr']['class'] = $this->getClassName($view);
        }
        $view->vars['actions'] = $options['actions'];
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
     * Gets the class name when the required option is set.
     */
    private function getClassName(FormView $view): string
    {
        $classes = $view->vars['attr']['class'] ?? '';
        if (false === \stripos($classes, 'must-validate')) {
            return \trim($classes . ' must-validate');
        }

        return $classes;
    }

    /**
     * Gets the definition of the default actions.
     */
    private function getDefaultActions(): array
    {
        return [
            // font
            [
                'exec' => 'formatBlock',
                'title' => 'header_h1',
                'icon' => 'heading',
                'group' => 'font',
                'parameter' => '<h1>',
            ],
            [
                'exec' => 'bold',
                'state' => 'bold',
                'icon' => 'bold',
                'group' => 'font',
            ],
            [
                'exec' => 'italic',
                'state' => 'italic',
                'icon' => 'italic',
                'group' => 'font',
            ],
            [
                'exec' => 'underline',
                'state' => 'underline',
                'icon' => 'underline',
                'group' => 'font',
            ],
            [
                'exec' => 'strikethrough',
                'state' => 'strikethrough',
                'icon' => 'strikethrough',
                'group' => 'font',
            ],

            // script
            [
                'exec' => 'superscript',
                'icon' => 'superscript',
                'group' => 'script',
            ],
            [
                'exec' => 'subscript',
                'icon' => 'subscript',
                'group' => 'script',
            ],

            // align
            [
                'exec' => 'justifyLeft',
                'state' => 'justifyLeft',
                'icon' => 'align-left',
                'group' => 'align',
            ],
            [
                'exec' => 'justifyCenter',
                'state' => 'justifyCenter',
                'icon' => 'align-center',
                'group' => 'align',
            ],
            [
                'exec' => 'justifyRight',
                'state' => 'justifyRight',
                'icon' => 'align-right',
                'group' => 'align',
            ],

            // paragraph
            [
                'exec' => 'indent',
                'icon' => 'indent',
                'group' => 'paragraph',
            ],
            [
                'exec' => 'outdent',
                'icon' => 'outdent',
                'group' => 'paragraph',
            ],

            // list
            [
                'exec' => 'insertOrderedList',
                'state' => 'insertOrderedList',
                'icon' => 'list-ol',
                'group' => 'list',
            ],
            [
                'exec' => 'insertUnorderedList',
                'state' => 'insertUnorderedList',
                'icon' => 'list-ul',
                'group' => 'list',
            ],

            // edit
            [
                'exec' => 'undo',
                'state' => 'undo',
                'enabled' => 'undo',
                'icon' => 'undo',
                'group' => 'edit',
            ],
            [
                'exec' => 'redo',
                'state' => 'redo',
                'enabled' => 'redo',
                'icon' => 'redo',
                'group' => 'edit',
            ],
        ];
    }
}
