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
use FOS\UserBundle\Form\Type\ResettingFormType;

/**
 * Extends FOS User bundle resettting type by adding the recaptcha.
 *
 * @author Laurent Muller
 */
class FosUserResettingFormType extends FosUserType
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
    public function getParent()
    {
        return ResettingFormType::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('username')
            ->label('security.login.username')
            ->domain('FOSUserBundle')
            ->addPlainType(true);

        parent::addFormFields($helper);
    }
}
