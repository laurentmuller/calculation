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

use App\Service\ThemeService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * The TinyMCE editor type.
 *
 * @author Laurent Muller
 */
class TinyMceEditorType extends AbstractType
{
    /**
     * The dark theme state.
     */
    protected bool $darkTheme;

    /**
     * Constructor.
     */
    public function __construct(ThemeService $service)
    {
        $this->darkTheme = $service->isDarkTheme();
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if ($options['required']) {
            $view->vars['attr']['class'] = $this->getWidgetClass($view);
        }
        $view->vars['attr']['data-skin'] = $this->darkTheme ? 'oxide-dark' : 'oxide';
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return TextareaType::class;
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
