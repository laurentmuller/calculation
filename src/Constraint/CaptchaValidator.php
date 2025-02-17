<?php

/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Constraint;

use App\Service\CaptchaImageService;
use Symfony\Component\Validator\Constraint;

/**
 * Captcha constraint validator.
 *
 * @extends AbstractConstraintValidator<Captcha>
 */
class CaptchaValidator extends AbstractConstraintValidator
{
    public function __construct(private readonly CaptchaImageService $service)
    {
        parent::__construct(Captcha::class);
    }

    /**
     * @param Captcha $constraint
     */
    #[\Override]
    protected function doValidate(string $value, Constraint $constraint): void
    {
        $this->service->setTimeout($constraint->timeout);
        if (!$this->service->validateTimeout()) {
            $this->context->buildViolation($constraint->timeout_message)
                ->setCode(Captcha::IS_TIMEOUT_ERROR)
                ->addViolation();
        } elseif (!$this->service->validateToken($value)) {
            $this->context->buildViolation($constraint->invalid_message)
                ->setCode(Captcha::IS_INVALID_ERROR)
                ->addViolation();
        }
    }
}
