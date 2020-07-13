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
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Type to update the user profile.
 *
 * @author Laurent Muller
 */
class ProfileEditType extends BaseType
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

        // user name
        $helper->field('username')
            ->label('form.username')
            ->domain('FOSUserBundle')
            ->autocomplete('username')
            ->add(UserNameType::class);

        // email
        $helper->field('email')
            ->label('form.email')
            ->domain('FOSUserBundle')
            ->autocomplete('email')
            ->addEmailType();

        // current password
        $helper->field('current_password')
            ->label('form.current_password')
            ->domain('FOSUserBundle')
            ->updateOption('constraints', [
                new NotBlank(),
                new UserPassword(['message' => 'fos_user.current_password.invalid']),
            ])
            ->updateOption('mapped', false)
            ->updateAttribute('autocomplete', 'current-password')
            ->add(PasswordType::class);

        // image
        $helper->field('imageFile')
            ->updateOption('delete_label', 'user.edit.delete_image')
            ->label('user.fields.image')
            ->addVichImageType();

        // id for ajax validation
        $helper->field('id')
            ->addHiddenType();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return '';
    }
}
