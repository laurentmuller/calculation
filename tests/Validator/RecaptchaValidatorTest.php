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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use ReCaptcha\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<RecaptchaValidator>
 */
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
    #[DataProvider('getErrorCodes')]
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
    public function testNoRequest(): void
    {
        $service = $this->createService();
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')
            ->willReturn(null);
        $validator = new RecaptchaValidator($service, $requestStack);
        $validator->initialize($this->context);
        $contraint = $this->createConstraint();
        $validator->validate('dummy', $contraint);
        $this->buildViolation('recaptcha.no-request')
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
    public function testSuccess(): void
    {
        $contraint = $this->createConstraint();
        $validator = $this->initValidator();
        $validator->validate('dummy', $contraint);
        self::assertNoViolation();
    }

    /**
     * @throws Exception
     */
    protected function createValidator(): RecaptchaValidator
    {
        $service = $this->createService();
        $requestStack = $this->createRequestStack();

        return new RecaptchaValidator($service, $requestStack);
    }

    private function createConstraint(): Recaptcha
    {
        return new Recaptcha();
    }

    /**
     * @throws Exception
     */
    private function createRequestStack(): RequestStack
    {
        $request = $this->createMock(Request::class);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')
            ->willReturn($request);

        return $requestStack;
    }

    /**
     * @throws Exception
     */
    private function createService(string $code = ''): RecaptchaService
    {
        $success = '' === $code;
        $errorCodes = $success ? [] : [$code];
        $response = new Response($success, $errorCodes);

        $service = $this->createMock(RecaptchaService::class);
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
        $requestStack = $this->createRequestStack();
        $this->validator = new RecaptchaValidator($service, $requestStack);
        $this->validator->initialize($this->context);

        return $this->validator;
    }
}
