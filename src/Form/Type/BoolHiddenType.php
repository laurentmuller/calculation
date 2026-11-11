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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Hidden type for boolean values.
 *
 * @extends AbstractType<HiddenType>
 */
class BoolHiddenType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            static fn (?bool $value): string => ($value ?? false) ? '1' : '0',
            static fn (?string $value): bool => \filter_var($value, \FILTER_VALIDATE_BOOLEAN)
        ));
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'mapped' => false,
        ]);
    }

    #[\Override]
    public function getParent(): string
    {
        return HiddenType::class;
    }
}
