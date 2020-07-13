<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Form;

use App\Form\Type\RepeatPasswordType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Type to change the user password.
 *
 * @author Laurent Muller
 */
class ChangePasswordFormType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $helper = new FormHelper($builder);

        $firstOptions = \array_replace_recursive(RepeatPasswordType::getFirstOptions(),
            ['label' => 'form.new_password']);
        $secondOptions = \array_replace_recursive(RepeatPasswordType::getSecondOptions(),
            ['label' => 'form.new_password_confirmation']);
        $helper->field('plainPassword')
            ->updateOption('first_options', $firstOptions)
            ->updateOption('second_options', $secondOptions)
            ->updateOption('mapped', false)
            ->add(RepeatPasswordType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        //$resolver->setDefaults([]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return '';
    }
}
