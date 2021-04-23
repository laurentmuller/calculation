<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Validator;

use ReCaptcha\ReCaptcha as ReCaptchaService;
use Symfony\Component\Validator\Constraint;

/**
 * Google reCaptcha constraint validator.
 *
 * @extends AbstractConstraintValidator<Recaptcha>
 *
 * @author Laurent Muller
 */
class RecaptchaValidator extends AbstractConstraintValidator
{
    /**
     * The reCaptcha secret key.
     */
    protected string $secret;

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
     *
     * @param Recaptcha $constraint
     */
    protected function doValidate($value, Constraint $constraint): void
    {
        $recaptcha = new ReCaptchaService($this->secret);
        $result = $recaptcha->verify($value);
        if (!$result->isSuccess()) {
            foreach ($result->getErrorCodes() as $code) {
                $this->context->addViolation("recaptcha.{$code}");
            }
        }
    }
}
