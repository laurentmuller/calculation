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

use App\Constraint\Password;
use App\Constraint\PasswordValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<PasswordValidator>
 */
final class PasswordValidatorTest extends ConstraintValidatorTestCase
{
    public static function getInvalidValues(): \Generator
    {
        yield ['abc', ['caseDiff' => true], 'password.caseDiff', Password::CASE_DIFF_ERROR];
        yield ['myemail@website.com', ['email' => true], 'password.email', Password::EMAIL_ERROR];
        yield ['123', ['letter' => true], 'password.letter', Password::LETTER_ERROR];
        yield ['@@@', ['letter' => true, 'numbers' => true], 'password.letter', Password::LETTER_ERROR];
        yield ['abc', ['number' => true], 'password.number', Password::NUMBER_ERROR];
        yield ['123', ['specialChar' => true], 'password.specialChar', Password::SPECIAL_CHAR_ERROR];
    }

    public static function getOptions(): \Generator
    {
        foreach (Password::ALLOWED_OPTIONS as $option) {
            yield [$option];
        }
        yield ['all'];
    }

    public static function getValidValues(): \Generator
    {
        yield ['ABC abc', 'caseDiff'];
        yield ['test', 'email'];
        yield ['abc', 'letters'];
        yield ['123', 'numbers'];
        yield ['123@', 'specialChar'];
        yield ['aB123456#*/82568A', 'all'];
    }

    public function testAll(): void
    {
        $options = [
            'all' => true,
            'caseDiff' => true,
            'email' => true,
            'letter' => true,
            'number' => true,
            'specialChar' => true,
        ];
        $constraint = $this->createConstraint($options);
        $this->validator->validate('zTp9F??TvRcG?+Z', $constraint);
        self::assertNoViolation();
    }

    public function testAllOption(): void
    {
        $constraint = $this->createConstraint([]);
        self::assertFalse($constraint->isOption('all'));
        $constraint->setOption('all', true);
        self::assertTrue($constraint->isOption('all'));
    }

    public function testAny(): void
    {
        $options = [
            'all' => false,
            'caseDiff' => true,
            'email' => true,
            'letter' => true,
            'number' => true,
            'specialChar' => true,
        ];
        $constraint = $this->createConstraint($options);
        $this->validator->validate('zTp9F??TvRcG?+Z', $constraint);
        self::assertNoViolation();
    }

    #[DataProvider('getOptions')]
    public function testEmptyIsValid(string $option): void
    {
        $constraint = $this->createConstraint([$option => true]);
        $this->validator->validate('', $constraint);
        self::assertNoViolation();
    }

    /**
     * @param array<string, bool> $options
     */
    #[DataProvider('getInvalidValues')]
    public function testInvalidValue(
        mixed $value,
        array $options,
        string $message,
        string $code,
        array $parameters = []
    ): void {
        $constraint = $this->createConstraint($options);
        $this->validator->validate($value, $constraint);
        $this->buildViolation($message)
            ->setParameters($parameters)
            ->setInvalidValue($value)
            ->setCode($code)
            ->assertRaised();
    }

    public function testIsOptionInvalid(): void
    {
        self::expectException(InvalidOptionsException::class);
        self::expectExceptionMessage('The option "fake" does not exist.');
        $constraint = $this->createConstraint([]);
        $constraint->isOption('fake');
    }

    #[DataProvider('getOptions')]
    public function testNullIsValid(string $option): void
    {
        $constraint = $this->createConstraint([$option => true]);
        $this->validator->validate(null, $constraint);
        self::assertNoViolation();
    }

    #[DataProvider('getOptions')]
    public function testOptionIsGet(string $option): void
    {
        $password = new Password();
        self::assertFalse($password->isOption($option));
        $password->setOption($option, true);
        self::assertTrue($password->isOption($option));
    }

    public function testSetOptionInvalid(): void
    {
        self::expectException(InvalidOptionsException::class);
        self::expectExceptionMessage('The option "fake" does not exist.');
        $constraint = $this->createConstraint([]);
        $constraint->setOption('fake', false);
    }

    #[DataProvider('getValidValues')]
    public function testValidValue(mixed $value, string $option): void
    {
        $constraint = $this->createConstraint([$option => true]);
        $this->validator->validate($value, $constraint);
        self::assertNoViolation();
    }

    #[\Override]
    protected function createValidator(): PasswordValidator
    {
        return new PasswordValidator();
    }

    /**
     * @param array<string, bool> $options
     */
    private function createConstraint(array $options): Password
    {
        return new Password(
            all: $options['all'] ?? false,
            letter: $options['letter'] ?? false,
            caseDiff: $options['caseDiff'] ?? false,
            number: $options['number'] ?? false,
            specialChar: $options['specialChar'] ?? false,
            email: $options['email'] ?? false,
        );
    }
}
