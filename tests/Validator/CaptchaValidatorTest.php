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

namespace App\Tests\Validator;

use App\Service\CaptchaImageService;
use App\Validator\Captcha;
use App\Validator\CaptchaValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<CaptchaValidator>
 */
#[CoversClass(CaptchaValidator::class)]
class CaptchaValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * @throws Exception
     */
    public function testEmptyIsValid(): void
    {
        $contraint = $this->createConstraint();
        $validator = $this->initValidator();
        $validator->validate('', $contraint);
        self::assertNoViolation();
    }

    /**
     * @throws Exception
     */
    public function testNullIsValid(): void
    {
        $contraint = $this->createConstraint();
        $validator = $this->initValidator();
        $validator->validate(null, $contraint);
        self::assertNoViolation();
    }

    /**
     * @throws Exception
     */
    public function testTimeoutInvalid(): void
    {
        $contraint = $this->createConstraint();
        $validator = $this->initValidator(false);
        $validator->validate('dummy', $contraint);
        $this->buildViolation('captcha.timeout')
            ->setCode(Captcha::IS_TIMEOUT_ERROR)
            ->assertRaised();
    }

    /**
     * @throws Exception
     */
    public function testTokenInvalid(): void
    {
        $contraint = $this->createConstraint();
        $validator = $this->initValidator(true, false);
        $validator->validate('dummy', $contraint);
        $this->buildViolation('captcha.invalid')
            ->setCode(Captcha::IS_INVALID_ERROR)
            ->assertRaised();
    }

    /**
     * @throws Exception
     */
    protected function createValidator(): CaptchaValidator
    {
        $service = $this->createService();

        return new CaptchaValidator($service);
    }

    private function createConstraint(): Captcha
    {
        return new Captcha();
    }

    /**
     * @throws Exception
     */
    private function createService(bool $validateTimeout = true, bool $validateToken = true): CaptchaImageService
    {
        $service = $this->createMock(CaptchaImageService::class);
        $service->expects(self::any())
            ->method('validateTimeout')
            ->willReturn($validateTimeout);
        $service->expects(self::any())
            ->method('validateToken')
            ->willReturn($validateToken);

        return $service;
    }

    /**
     * @throws Exception
     */
    private function initValidator(bool $validateTimeout = true, bool $validateToken = true): CaptchaValidator
    {
        $service = $this->createService($validateTimeout, $validateToken);
        $this->validator = new CaptchaValidator($service);
        $this->validator->initialize($this->context);

        return $this->validator;
    }
}
