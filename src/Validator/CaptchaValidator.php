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

namespace App\Validator;

use App\Service\CaptchaImageService;
use Symfony\Component\Validator\Constraint;

/**
 * Captcha constraint validator.
 *
 * @author Laurent Muller
 */
class CaptchaValidator extends AbstractConstraintValidator
{
    /**
     * @var CaptchaImageService
     */
    protected $service;

    /**
     * Constructor.
     */
    public function __construct(CaptchaImageService $service)
    {
        $this->service = $service;
        parent::__construct(Captcha::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function doValidate($value, Constraint $constraint): void
    {
        if (!$this->service->validateTimeout()) {
            $this->context->addViolation('captcha.timeout');
        } elseif (!$this->service->validateToken($value)) {
            $this->context->addViolation('captcha.invalid');
        }
    }
}
