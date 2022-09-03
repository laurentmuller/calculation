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
 * Unit test for {@link PasswordValidator} class.
 *
 * @extends ConstraintValidatorTestCase<PasswordValidator>
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PasswordValidatorTest extends ConstraintValidatorTestCase
{
    public function getConstraints(): array
    {
        return [
            ['case_diff'],
            ['email'],
            ['letters'],
            ['numbers'],
            ['special_char'],
            ['pwned'],
        ];
    }

    public function getInvalidValues(): array
    {
        return [
            ['abc', ['case_diff' => true], 'password.case_diff', Password::CASE_DIFF_ERROR],
            ['myemail@website.com', ['email' => true], 'password.email', Password::EMAIL_ERROR],
            ['123', ['letters' => true], 'password.letters', Password::LETTERS_ERROR],
            ['@@@', ['letters' => true, 'numbers' => true], 'password.letters', Password::LETTERS_ERROR],
            ['abc', ['numbers' => true], 'password.numbers', Password::NUMBERS_ERROR],
            ['123', ['special_char' => true], 'password.special_char', Password::SPECIAL_CHAR_ERROR],
        ];
    }

    public function getPasswords(): array
    {
        return [
            ['123456', true],
            ['123*9-*55sA', false],
        ];
    }

    public function getValidValues(): array
    {
        return [
            ['ABC abc', ['case_diff' => true]],
            ['test', ['email' => true]],
            ['abc', ['letters' => true]],
            ['123', ['numbers' => true]],
            ['123*9-*55sA', ['pwned' => true]],
            ['123@', ['special_char' => true]],
        ];
    }

    /**
     * @dataProvider getConstraints
     */
    public function testEmptyStringIsValid(string $constraint): void
    {
        $constraint = $this->createPassword([$constraint => true]);
        $this->validator->validate('', $constraint);
        self::assertNoViolation();
    }

    /**
     * @param mixed $value the value to be tested
     *
     * @dataProvider getInvalidValues
     */
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

    /**
     * @dataProvider getConstraints
     */
    public function testNullIsValid(string $constraint): void
    {
        $constraint = $this->createPassword([$constraint => true]);
        $this->validator->validate(null, $constraint);
        self::assertNoViolation();
    }

    /**
     * @dataProvider getPasswords
     */
    public function testPwned(string $value, bool $violation): void
    {
        $options = ['pwned' => true];
        $constraint = $this->createPassword($options);
        $this->validator->validate($value, $constraint);
        if ($violation) {
            $violations = $this->context->getViolations();
            self::assertCount(1, $violations);
            $first = $violations[0];
            self::assertSame('password.pwned', $first->getMessageTemplate());
            self::assertSame($value, $first->getInvalidValue());
        } else {
            self::assertNoViolation();
        }
    }

    /**
     * @param mixed $value the value to be tested
     *
     * @dataProvider getValidValues
     */
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
