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
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<PasswordValidator>
 */
class PasswordValidatorTest extends ConstraintValidatorTestCase
{
    public static function getConstraints(): \Iterator
    {
        yield ['case_diff'];
        yield ['email'];
        yield ['letters'];
        yield ['numbers'];
        yield ['special_char'];
        yield ['compromised'];
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
        yield ['123*9-*55sA', ['compromised' => true]];
        yield ['123@', ['special_char' => true]];
    }

    public function testAll(): void
    {
        $constraint = $this->createPassword(['all' => true]);
        $this->validator->validate('zTp9F??TvRcG?+Z', $constraint);
        self::assertNoViolation();
    }

    #[DataProvider('getPasswords')]
    public function testCompromised(string $value, bool $violation): void
    {
        $options = ['compromised' => true];
        $constraint = $this->createPassword($options);
        $this->validator->validate($value, $constraint);
        if ($violation) {
            $violations = $this->context->getViolations();
            self::assertCount(1, $violations);
            $first = $violations[0];
            self::assertNotNull($first);
            self::assertSame('password.compromised', $first->getMessageTemplate());
            self::assertSame($value, $first->getInvalidValue());
        } else {
            self::assertNoViolation();
        }
    }

    #[DataProvider('getConstraints')]
    public function testEmptyIsValid(string $constraint): void
    {
        $constraint = $this->createPassword([$constraint => true]);
        $this->validator->validate('', $constraint);
        self::assertNoViolation();
    }

    /**
     * @param array<string, bool> $options
     */
    #[DataProvider('getInvalidValues')]
    public function testInvalid(
        mixed $value,
        array $options,
        string $message,
        string $code,
        array $parameters = []
    ): void {
        $constraint = $this->createPassword($options);
        $this->validator->validate($value, $constraint);
        $this->buildViolation($message)
            ->setParameters($parameters)
            ->setInvalidValue($value)
            ->setCode($code)
            ->assertRaised();
    }

    #[DataProvider('getConstraints')]
    public function testNullIsValid(string $constraint): void
    {
        $constraint = $this->createPassword([$constraint => true]);
        $this->validator->validate(null, $constraint);
        self::assertNoViolation();
    }

    /**
     * @param array<string, bool> $options
     */
    #[DataProvider('getValidValues')]
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

    /**
     * @param array<string, bool> $options
     */
    private function createPassword(array $options): Password
    {
        return new Password(
            all: $options['all'] ?? false,
            letters: $options['letters'] ?? false,
            case_diff: $options['case_diff'] ?? false,
            numbers: $options['numbers'] ?? false,
            special_char: $options['special_char'] ?? false,
            email: $options['email'] ?? false,
            compromised: $options['compromised'] ?? false,
        );
    }
}
