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
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<CaptchaValidator>
 */
#[AllowMockObjectsWithoutExpectations]
final class CaptchaValidatorTest extends ConstraintValidatorTestCase
{
    public function testEmptyIsValid(): void
    {
        $constraint = $this->createConstraint();
        $validator = $this->initValidator();
        $validator->validateInContext('', $constraint, $this->context);
        self::assertNoViolation();
    }

    public function testNullIsValid(): void
    {
        $constraint = $this->createConstraint();
        $validator = $this->initValidator();
        $validator->validateInContext(null, $constraint, $this->context);
        self::assertNoViolation();
    }

    public function testTimeoutInvalid(): void
    {
        $constraint = $this->createConstraint();
        $validator = $this->initValidator(false);
        $validator->validateInContext('dummy', $constraint, $this->context);
        $this->buildViolation('captcha.timeout')
            ->setCode(Captcha::TIMEOUT_ERROR)
            ->assertRaised();
    }

    public function testTokenInvalid(): void
    {
        $constraint = $this->createConstraint();
        $validator = $this->initValidator(true, false);
        $validator->validateInContext('dummy', $constraint, $this->context);
        $this->buildViolation('captcha.invalid')
            ->setCode(Captcha::INVALID_ERROR)
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
        $service = self::createStub(CaptchaImageService::class);
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

        return $this->validator;
    }
}
