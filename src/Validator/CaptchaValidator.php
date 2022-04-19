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

namespace App\Validator;

use App\Service\CaptchaImageService;
use Symfony\Component\Validator\Constraint;

/**
 * Captcha constraint validator.
 *
 * @extends AbstractConstraintValidator<Captcha>
 *
 * @author Laurent Muller
 */
class CaptchaValidator extends AbstractConstraintValidator
{
    /**
     * Constructor.
     */
    public function __construct(private readonly CaptchaImageService $service)
    {
        parent::__construct(Captcha::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function doValidate(string $value, Constraint $constraint): void
    {
        if (!$this->service->validateTimeout()) {
            $this->context->addViolation('captcha.timeout');
        } elseif (!$this->service->validateToken($value)) {
            $this->context->addViolation('captcha.invalid');
        }
    }
}
