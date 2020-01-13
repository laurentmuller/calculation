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

use App\Service\ApplicationService;
use App\Service\CaptchaImageService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Extends FOS User bundle reset password type by adding the user name and the captcha.
 *
 * @author Laurent Muller
 */
class FosUserResetPasswordType extends FosUserType
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
        parent::__construct($service, $application);
        $this->remote = $generator->generate('ajax_check_exist');
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('username')
            ->label('resetting.request.username')
            ->className('user-name')
            ->domain('FOSUserBundle')
            ->updateAttribute('remote', $this->remote)
            ->add(UserNameType::class);

        parent::addFormFields($helper);
    }
}
