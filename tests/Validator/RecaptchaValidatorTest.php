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

use App\Service\RecaptchaService;
use App\Validator\Recaptcha;
use App\Validator\RecaptchaValidator;
use PHPUnit\Framework\MockObject\Exception;
use ReCaptcha\Response;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<RecaptchaValidator>
 */
#[\PHPUnit\Framework\Attributes\CoversClass(RecaptchaValidator::class)]
class RecaptchaValidatorTest extends ConstraintValidatorTestCase
{
    public static function getErrorCodes(): \Iterator
    {
        yield ['bad-request'];
        yield ['bad-response'];
    }

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
    #[\PHPUnit\Framework\Attributes\DataProvider('getErrorCodes')]
    public function testErrorCodeIsInvalid(string $code): void
    {
        $contraint = $this->createConstraint();
        $validator = $this->initValidator($code);
        $validator->validate('dummy', $contraint);
        $this->buildViolation("recaptcha.$code")
            ->setCode($code)
            ->assertRaised();
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
    private function createService(string $code = ''): RecaptchaService
    {
        $service = $this->getMockBuilder(RecaptchaService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $success = '' === $code;
        $errorCodes = $success ? [] : [$code];
        $response = new Response($success, $errorCodes);
        $service->method('verify')
            ->willReturn($response);

        return $service;
    }

    /**
     * @throws Exception
     */
    private function initValidator(string $code = ''): RecaptchaValidator
    {
        $service = $this->createService($code);
        $this->validator = new RecaptchaValidator($service);
        $this->validator->initialize($this->context);

        return $this->validator;
    }
}
