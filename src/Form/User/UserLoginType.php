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

namespace App\Form\User;

use App\Form\FormHelper;
use App\Service\ApplicationService;
use App\Service\CaptchaImageService;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * User login type.
 *
 * @author Laurent Muller
 */
class UserLoginType extends UserCaptchaType
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
    protected function addFormFields(FormHelper $helper, FormBuilderInterface $builder, array $options): void
    {
        $helper->field('username')
            ->autocomplete('username')
            ->maxLength(180)
            ->add(UserNameType::class);

        $helper->field('password')
            ->autocomplete('current-password')
            ->maxLength(255)
            ->addPassordType();

        parent::addFormFields($helper, $builder, $options);

        $helper->field('remember_me')
            ->updateRowAttribute('class', 'text-right')
            ->notRequired()
            ->addCheckboxType();
    }

    /**
     * {@inheritdoc}
     */
    protected function getLabelPrefix(): ?string
    {
        return 'security.login.';
    }
}
