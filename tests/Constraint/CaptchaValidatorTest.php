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

namespace App\Tests\Constraint;

use App\Constraint\Captcha;
use App\Constraint\CaptchaValidator;
use App\Service\CaptchaImageService;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<CaptchaValidator>
 */
class CaptchaValidatorTest extends ConstraintValidatorTestCase
{
    public function testEmptyIsValid(): void
    {
        $contraint = $this->createConstraint();
        $validator = $this->initValidator();
        $validator->validate('', $contraint);
        self::assertNoViolation();
    }

    public function testNullIsValid(): void
    {
        $contraint = $this->createConstraint();
        $validator = $this->initValidator();
        $validator->validate(null, $contraint);
        self::assertNoViolation();
    }

    public function testTimeoutInvalid(): void
    {
        $contraint = $this->createConstraint();
        $validator = $this->initValidator(false);
        $validator->validate('dummy', $contraint);
        $this->buildViolation('captcha.timeout')
            ->setCode(Captcha::IS_TIMEOUT_ERROR)
            ->assertRaised();
    }

    public function testTokenInvalid(): void
    {
        $contraint = $this->createConstraint();
        $validator = $this->initValidator(true, false);
        $validator->validate('dummy', $contraint);
        $this->buildViolation('captcha.invalid')
            ->setCode(Captcha::IS_INVALID_ERROR)
            ->assertRaised();
    }

    #[\Override]
    protected function createValidator(): CaptchaValidator
    {
        $service = $this->createService();

        return new CaptchaValidator($service);
    }

    private function createConstraint(): Captcha
    {
        return new Captcha();
    }

    private function createService(bool $validateTimeout = true, bool $validateToken = true): CaptchaImageService
    {
        $service = $this->createMock(CaptchaImageService::class);
        $service->method('validateTimeout')
            ->willReturn($validateTimeout);
        $service->method('validateToken')
            ->willReturn($validateToken);

        return $service;
    }

    private function initValidator(bool $validateTimeout = true, bool $validateToken = true): CaptchaValidator
    {
        $service = $this->createService($validateTimeout, $validateToken);
        $this->validator = new CaptchaValidator($service);
        $this->validator->initialize($this->context);

        return $this->validator;
    }
}
