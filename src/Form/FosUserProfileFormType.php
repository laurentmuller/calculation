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
use FOS\UserBundle\Form\Type\ProfileFormType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Extends FOS User bundle profile type by adding the image.
 *
 * @author Laurent Muller
 */
class FosUserProfileFormType extends ProfileFormType
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

        // add image
        $helper->field('imageFile')
            ->updateOption('delete_label', 'user.edit.delete_image')
            ->label('user.fields.image')
            ->addVichImageType();

        // add id for ajax validation
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

    /**
     * {@inheritdoc}
     */
    protected function buildUserForm(FormBuilderInterface $builder, array $options): void
    {
        $helper = new FormHelper($builder);

        $helper->field('username')
            ->label('form.username')
            ->domain('FOSUserBundle')
            ->autocomplete('username')
            ->add(UserNameType::class);

        $helper->field('email')
            ->label('form.email')
            ->domain('FOSUserBundle')
            ->autocomplete('email')
            ->addEmailType();
    }
}
