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
use App\Interfaces\PropertyServiceInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<PasswordValidator>
 */
class PasswordValidatorTest extends ConstraintValidatorTestCase
{
    public static function getInvalidValues(): \Iterator
    {
        yield ['abc', ['case_diff' => true], 'password.case_diff', Password::CASE_DIFF_ERROR];
        yield ['myemail@website.com', ['email' => true], 'password.email', Password::EMAIL_ERROR];
        yield ['123', ['letters' => true], 'password.letters', Password::LETTERS_ERROR];
        yield ['@@@', ['letters' => true, 'numbers' => true], 'password.letters', Password::LETTERS_ERROR];
        yield ['abc', ['numbers' => true], 'password.numbers', Password::NUMBERS_ERROR];
        yield ['123', ['special_char' => true], 'password.special_char', Password::SPECIAL_CHAR_ERROR];
    }

    public static function getOptions(): \Iterator
    {
        foreach (PropertyServiceInterface::PASSWORD_OPTIONS as $option) {
            yield [$option];
        }
        yield ['all'];
    }

    public static function getValidValues(): \Iterator
    {
        yield ['ABC abc', 'case_diff'];
        yield ['test', 'email'];
        yield ['abc', 'letters'];
        yield ['123', 'numbers'];
        yield ['123@', 'special_char'];
        yield ['aB123456#*/82568A', 'all'];
    }

    public function testAll(): void
    {
        $options = [
            'all' => true,
            'case_diff' => true,
            'email' => true,
            'letters' => true,
            'numbers' => true,
            'special_char' => true,
        ];
        $constraint = $this->createConstraint($options);
        $this->validator->validate('zTp9F??TvRcG?+Z', $constraint);
        self::assertNoViolation();
    }

    public function testAny(): void
    {
        $options = [
            'all' => false,
            'case_diff' => true,
            'email' => true,
            'letters' => true,
            'numbers' => true,
            'special_char' => true,
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

    public function testGetInvalidOptions(): void
    {
        self::expectException(InvalidOptionsException::class);
        $constraint = $this->createConstraint([]);
        $constraint->getOption('fake');
    }

    public function testGetSetAllOption(): void
    {
        $constraint = $this->createConstraint([]);
        self::assertFalse($constraint->getOption('all'));
        $constraint->setOption('all', true);
        self::assertTrue($constraint->getOption('all'));
    }

    #[DataProvider('getOptions')]
    public function testGetSetOptions(string $option): void
    {
        $password = new Password();
        self::assertFalse($password->getOption($option));
        $password->setOption($option, true);
        self::assertTrue($password->getOption($option));
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

    #[DataProvider('getOptions')]
    public function testNullIsValid(string $option): void
    {
        $constraint = $this->createConstraint([$option => true]);
        $this->validator->validate(null, $constraint);
        self::assertNoViolation();
    }

    public function testSetInvalidOptions(): void
    {
        self::expectException(InvalidOptionsException::class);
        $constraint = $this->createConstraint([]);
        $constraint->setOption('fake', 'fake');
    }

    #[DataProvider('getValidValues')]
    public function testValidValue(mixed $value, string $option): void
    {
        $constraint = $this->createConstraint([$option => true]);
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
    private function createConstraint(array $options): Password
    {
        return new Password(
            all: $options['all'] ?? false,
            letters: $options['letters'] ?? false,
            case_diff: $options['case_diff'] ?? false,
            numbers: $options['numbers'] ?? false,
            special_char: $options['special_char'] ?? false,
            email: $options['email'] ?? false,
        );
    }
}
