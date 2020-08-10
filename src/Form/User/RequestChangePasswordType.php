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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Type to request change password of a user.
 *
 * @author Laurent Muller
 */
class RequestChangePasswordType extends UserCaptchaType
{
    /**
     * @var string
     */
    private $remote;

    /**
     * Constructor.
     */
    public function __construct(CaptchaImageService $service, ApplicationService $application, UrlGeneratorInterface $generator)
    {
        $this->remote = $generator->generate('ajax_check_exist');
        parent::__construct($service, $application);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper, FormBuilderInterface $builder, array $options): void
    {
        $helper->field('username')
            ->label('resetting.request.username')
            ->className('user-name')
            ->updateAttribute('remote', $this->remote)
            ->add(UserNameType::class);

        parent::addFormFields($helper, $builder, $options);
    }
}
