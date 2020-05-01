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
use App\Service\ApplicationService;
use App\Service\CaptchaImageService;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Replace the FOS User bundle registration type.
 *
 * @author Laurent Muller
 */
class FosUserRegistrationType extends FosUserType
{
    /**
     * Constructor.
     */
    public function __construct(CaptchaImageService $service, ApplicationService $application)
    {
        parent::__construct($service, $application);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_token_id' => 'registration',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('email')
            ->label('form.email')
            ->domain('FOSUserBundle')
            ->addEmailType();

        $helper->field('username')
            ->label('form.username')
            ->autocomplete('username')
            ->maxLength(180)
            ->domain('FOSUserBundle')
            ->add(UserNameType::class);

        $firstOptions = \array_replace_recursive(RepeatPasswordType::getFirstOptions(),
            ['label' => 'form.password']);

        $secondOptions = \array_replace_recursive(RepeatPasswordType::getSecondOptions(),
            ['label' => 'form.new_password_confirmation']);

        $helper->field('plainPassword')
            ->updateOption('first_options', $firstOptions)
            ->updateOption('second_options', $secondOptions)
            ->add(RepeatPasswordType::class);

        parent::addFormFields($helper);
    }
}
