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

/**
 * Extends FOS User bundle reset password type by adding the user name and the recaptcha.
 *
 * @author Laurent Muller
 */
class FosUserResetPasswordType extends FosUserType
{
    /**
     * Constructor.
     *
     * @param CaptchaImageService $service     the image service
     * @param ApplicationService  $application the application service
     */
    public function __construct(CaptchaImageService $service, ApplicationService $application)
    {
        parent::__construct($service, $application);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('username')
            ->label('resetting.request.username')
            ->domain('FOSUserBundle')
            ->addTextType();

        parent::addFormFields($helper);
    }
}
