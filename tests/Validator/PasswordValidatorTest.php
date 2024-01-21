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

use App\Validator\Password;
use App\Validator\PasswordValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<PasswordValidator>
 */
#[\PHPUnit\Framework\Attributes\CoversClass(PasswordValidator::class)]
class PasswordValidatorTest extends ConstraintValidatorTestCase
{
    public static function getConstraints(): \Iterator
    {
        yield ['case_diff'];
        yield ['email'];
        yield ['letters'];
        yield ['numbers'];
        yield ['special_char'];
        yield ['pwned'];
    }

    public static function getInvalidValues(): \Iterator
    {
        yield ['abc', ['case_diff' => true], 'password.case_diff', Password::CASE_DIFF_ERROR];
        yield ['myemail@website.com', ['email' => true], 'password.email', Password::EMAIL_ERROR];
        yield ['123', ['letters' => true], 'password.letters', Password::LETTERS_ERROR];
        yield ['@@@', ['letters' => true, 'numbers' => true], 'password.letters', Password::LETTERS_ERROR];
        yield ['abc', ['numbers' => true], 'password.numbers', Password::NUMBERS_ERROR];
        yield ['123', ['special_char' => true], 'password.special_char', Password::SPECIAL_CHAR_ERROR];
    }

    public static function getPasswords(): \Iterator
    {
        yield ['123456', true];
        yield ['123*9-*55sA', false];
    }

    public static function getValidValues(): \Iterator
    {
        yield ['ABC abc', ['case_diff' => true]];
        yield ['test', ['email' => true]];
        yield ['abc', ['letters' => true]];
        yield ['123', ['numbers' => true]];
        yield ['123*9-*55sA', ['pwned' => true]];
        yield ['123@', ['special_char' => true]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getConstraints')]
    public function testEmptyIsValid(string $constraint): void
    {
        $constraint = $this->createPassword([$constraint => true]);
        $this->validator->validate('', $constraint);
        self::assertNoViolation();
    }

    /**
     * @param mixed $value the value to be tested
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getInvalidValues')]
    public function testInvalid(mixed $value, array $options, string $message, string $code, array $parameters = []): void
    {
        $constraint = $this->createPassword($options);
        $this->validator->validate($value, $constraint);
        $this->buildViolation($message)
            ->setParameters($parameters)
            ->setInvalidValue($value)
            ->setCode($code)
            ->assertRaised();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getConstraints')]
    public function testNullIsValid(string $constraint): void
    {
        $constraint = $this->createPassword([$constraint => true]);
        $this->validator->validate(null, $constraint);
        self::assertNoViolation();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getPasswords')]
    public function testPwned(string $value, bool $violation): void
    {
        $options = ['pwned' => true];
        $constraint = $this->createPassword($options);
        $this->validator->validate($value, $constraint);
        if ($violation) {
            $violations = $this->context->getViolations();
            self::assertCount(1, $violations);
            $first = $violations[0];
            self::assertNotNull($first);
            self::assertSame('password.pwned', $first->getMessageTemplate());
            self::assertSame($value, $first->getInvalidValue());
        } else {
            self::assertNoViolation();
        }
    }

    /**
     * @param mixed $value the value to be tested
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getValidValues')]
    public function testValid(mixed $value, array $options): void
    {
        $constraint = $this->createPassword($options);
        $this->validator->validate($value, $constraint);
        self::assertNoViolation();
    }

    protected function createValidator(): PasswordValidator
    {
        return new PasswordValidator();
    }

    private function createPassword(array $options): Password
    {
        $options = \array_merge([
            'all' => false,
            'case_diff' => false,
            'email' => false,
            'letters' => false,
            'numbers' => false,
            'pwned' => false,
            'special_char' => false,
        ], $options);

        return new Password($options);
    }
}
