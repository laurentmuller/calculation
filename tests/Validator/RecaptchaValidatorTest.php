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

use App\Validator\Recaptcha;
use App\Validator\RecaptchaValidator;
use PHPUnit\Framework\MockObject\Exception;
use ReCaptcha\ReCaptcha as ReCaptchaService;
use ReCaptcha\Response;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<RecaptchaValidator>
 */
#[\PHPUnit\Framework\Attributes\CoversClass(RecaptchaValidator::class)]
class RecaptchaValidatorTest extends ConstraintValidatorTestCase
{
    public static function getErrorCodes(): array
    {
        return [
            ['bad-request'],
            ['bad-response'],
        ];
    }

    public function testEmptyIsValid(): void
    {
        $contraint = $this->createConstraint();
        $this->validator->validate('', $contraint);
        self::assertNoViolation();
    }

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getErrorCodes')]
    public function testErrorCodeIsInvalid(string $code): void
    {
        $contraint = $this->createConstraint();
        $service = $this->createService($code);
        $this->validator = new RecaptchaValidator($service);
        $this->validator->initialize($this->context);
        $this->validator->validate('dummy', $contraint);
        $this->buildViolation("recaptcha.$code")
            ->setCode($code)
            ->assertRaised();
    }

    public function testNullIsValid(): void
    {
        $contraint = $this->createConstraint();
        $this->validator->validate(null, $contraint);
        self::assertNoViolation();
    }

    /**
     * @throws Exception
     */
    protected function createValidator(): RecaptchaValidator
    {
        $service = $this->createService();

        return new RecaptchaValidator($service);
    }

    private function createConstraint(): Recaptcha
    {
        return new Recaptcha();
    }

    /**
     * @throws Exception
     */
    private function createService(string $code = ''): ReCaptchaService
    {
        $service = $this->getMockBuilder(ReCaptchaService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $success = '' === $code;
        $errorCodes = $success ? [] : [$code];
        $response = new Response($success, $errorCodes);
        $service->method('verify')
            ->willReturn($response);

        return $service;
    }
}
