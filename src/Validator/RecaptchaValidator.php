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

use ReCaptcha\ReCaptcha;
use Symfony\Component\Validator\Constraint;

/**
 * Google reCaptcha constraint validator.
 *
 * @author Laurent Muller
 */
class RecaptchaValidator extends AbstractConstraintValidator
{
    /**
     * The reCaptcha secret key.
     *
     * @var string
     */
    protected $secret;

    /**
     * Constructor.
     *
     * @param string $secret the reCaptcha secret key
     */
    public function __construct(string $secret)
    {
        $this->secret = $secret;
        parent::__construct(Recaptcha::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function doValidate($value, Constraint $constraint): void
    {
        $recaptcha = new ReCaptcha($this->secret);
        $result = $recaptcha->verify($value);
        if (!$result->isSuccess()) {
            foreach ($result->getErrorCodes() as $code) {
                $this->context->addViolation("recaptcha.{$code}");
            }
        }
    }
}
