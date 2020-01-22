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

use App\Entity\User;
use App\Form\Type\RepeatPasswordType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Change user password type.
 *
 * @author Laurent Muller
 */
class UserChangePasswordType extends BaseType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(User::class);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $helper = new FormHelper($builder);

        $helper->field('username')
            ->label('form.username')
            ->domain('FOSUserBundle')
            ->updateOption('hidden_input', true)
            ->addPlainType(true);

        $helper->field('plainPassword')
            ->updateOption('first_options', [
                'label' => 'form.new_password',
                'attr' => [
                    'minLength' => 6,
                    'maxLength' => 255,
                    'class' => 'password-strength',
                    'autocomplete' => 'new-password',
                ],
            ])
            ->updateOption('second_options', [
                'label' => 'form.new_password_confirmation',
            ])
            ->add(RepeatPasswordType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return '';
    }
}
