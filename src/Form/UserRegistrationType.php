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
 * Type to register a new user.
 *
 * @author Laurent Muller
 */
class UserRegistrationType extends UserCaptchaType
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
        $resolver->setDefaults(['data_class' => User::class]);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('email')
            ->label('user.fields.email')
            ->addEmailType();

        $helper->field('username')
            ->label('user.fields.username')
            ->autocomplete('username')
            ->maxLength(180)
            ->add(UserNameType::class);

        $helper->field('plainPassword')
            ->add(RepeatPasswordType::class);

        parent::addFormFields($helper);
    }
}
