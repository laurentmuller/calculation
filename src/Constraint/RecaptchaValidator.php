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

use App\Service\RecaptchaService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;

/**
 * Google reCaptcha constraint validator.
 *
 * @extends AbstractConstraintValidator<Recaptcha>
 */
class RecaptchaValidator extends AbstractConstraintValidator
{
    public function __construct(private readonly RecaptchaService $service, private readonly RequestStack $requestStack)
    {
        parent::__construct(Recaptcha::class);
    }

    /**
     * @param Recaptcha $constraint
     */
    protected function doValidate(string $value, Constraint $constraint): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            $this->context->buildViolation($this->getMessage('no-request'))
                ->addViolation();

            return;
        }

        $response = $this->service->verify($value, $request);
        if ($response->isSuccess()) {
            return;
        }

        /** @var string[] $errorCodes */
        $errorCodes = $response->getErrorCodes();
        foreach ($errorCodes as $code) {
            $this->context->buildViolation($this->getMessage($code))
                ->setCode($code)
                ->addViolation();
        }
    }

    private function getMessage(string $code): string
    {
        return RecaptchaService::ERROR_PREFIX . $code;
    }
}
