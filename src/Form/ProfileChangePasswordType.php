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
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Type to change the proilfe of the current (logged) user.
 *
 * @author Laurent Muller
 */
class ProfileChangePasswordType extends BaseType
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
        $helper = new FormHelper($builder);

        // current password
        $helper->field('current_password')
            ->label('user.password.current')
            ->updateOption('constraints', [
                new NotBlank(),
                new UserPassword(['message' => 'current_password.invalid']),
            ])
            ->updateOption('mapped', false)
            ->updateAttribute('autocomplete', 'current-password')
            ->add(PasswordType::class);

        $firstOptions = \array_replace_recursive(RepeatPasswordType::getFirstOptions(),
                ['label' => 'user.password.new']);
        $secondOptions = \array_replace_recursive(RepeatPasswordType::getSecondOptions(),
                ['label' => 'user.password.new_confirmation']);
        $helper->field('plainPassword')
            ->updateOption('first_options', $firstOptions)
            ->updateOption('second_options', $secondOptions)
            ->add(RepeatPasswordType::class);

        // username for ajax validation
        $builder->add('username', HiddenType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return '';
    }
}
