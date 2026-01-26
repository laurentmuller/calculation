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

use App\Form\Type\PlainType;
use Elao\Enum\Bridge\Symfony\Form\Type\EnumType as ElaoEnumType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Type extension for Bootstrap prepend and append input groups.
 */
class InputGroupTypeExtension extends AbstractTypeExtension
{
    private const array OPTIONS = [
        'prepend_icon',
        'prepend_title',
        'prepend_class',
        'append_icon',
        'append_title',
        'append_class',
    ];

    /**
     * @phpstan-param array<array-key, mixed> $options
     */
    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        foreach (self::OPTIONS as $option) {
            if (isset($options[$option])) {
                $view->vars[$option] = $options[$option];
            }
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(self::OPTIONS);
        foreach (self::OPTIONS as $option) {
            $resolver->setAllowedTypes($option, ['null', 'string']);
        }
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [
            TextType::class,
            NumberType::class,
            ChoiceType::class,
            PlainType::class,
            EnumType::class,
            ElaoEnumType::class,
        ];
    }
}
