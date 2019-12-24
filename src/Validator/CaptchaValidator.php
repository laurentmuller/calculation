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
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Captcha validator.
 *
 * @author Laurent Muller
 */
class CaptchaValidator extends ConstraintValidator
{
    /**
     * @var CaptchaImageService
     */
    protected $service;

    /**
     * Constructor.
     *
     * @param CaptchaImageService $service the service to validate input
     */
    public function __construct(CaptchaImageService $service)
    {
        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        // check constraint type
        if (!$constraint instanceof Captcha) {
            throw new UnexpectedTypeException($constraint, Captcha::class);
        }

        // verify
        if (!$this->service->validateTimeout()) {
            $this->context->addViolation('captcha.timeout');
        } elseif (!$this->service->validateToken((string) $value)) {
            $this->context->addViolation('captcha.invalid');
        }
    }
}
