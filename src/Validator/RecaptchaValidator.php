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

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Google reCaptcha validator.
 *
 * @author Laurent Muller
 */
class RecaptchaValidator extends ConstraintValidator
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
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        // check constraint type
        if (!$constraint instanceof Recaptcha) {
            throw new UnexpectedTypeException($constraint, Recaptcha::class);
        }

        // verify
        $recaptcha = new \ReCaptcha\ReCaptcha($this->secret);
        $result = $recaptcha->verify((string) $value);

        // ok?
        if (!$result->isSuccess()) {
            foreach ($result->getErrorCodes() as $code) {
                $this->context->addViolation("recaptcha.{$code}");
            }
        }
    }
}
