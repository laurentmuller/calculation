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

namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Extends the text type by allowing to add input groups (prepend or append).
 *
 * @extends AbstractTypeExtension<TextType>
 */
class TextTypeExtension extends AbstractTypeExtension
{
    private const STRING_OPTIONS = [
        'prepend_icon',
        'prepend_title',
        'prepend_class',
        'append_icon',
        'append_title',
        'append_class',
    ];

    /**
     * @psalm-param array<array-key, mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        foreach (self::STRING_OPTIONS as $option) {
            if (isset($options[$option])) {
                $view->vars[$option] = $options[$option];
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        foreach (self::STRING_OPTIONS as $option) {
            $resolver->setDefined($option)
                ->setAllowedTypes($option, ['null', 'string']);
        }
    }

    public static function getExtendedTypes(): iterable
    {
        return [TextType::class];
    }
}
