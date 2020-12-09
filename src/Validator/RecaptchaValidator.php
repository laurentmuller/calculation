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
