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

use App\Interfaces\StrengthInterface;
use App\Validator\Password;
use App\Validator\PasswordValidator;

use function PHPUnit\Framework\assertSame;

use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Unit test for {@link PasswordValidator} class.
 *
 * @extends ConstraintValidatorTestCase<PasswordValidator>
 */
class PasswordValidatorTest extends ConstraintValidatorTestCase
{
    private const EMPTY_MESSAGE = 'empty';

    public function getConstraints(): array
    {
        return [
            ['case_diff'],
            ['email'],
            ['letters'],
            ['numbers'],
            ['special_char'],
            ['pwned'],
            ['min_strength', StrengthInterface::LEVEL_VERY_STRONG],
        ];
    }

    public function getInvalidValues(): array
    {
        return [
            ['abc', ['case_diff' => true], 'password.case_diff'],
            ['myemail@website.com', ['email' => true], 'password.email'],
            ['123', ['letters' => true], 'password.letters'],
            ['abc', ['numbers' => true], 'password.numbers'],
            ['123', ['special_char' => true], 'password.special_char'],
            ['@@@', ['letters' => true, 'numbers' => true], 'password.letters'],
        ];
    }

    public function getPasswords(): array
    {
        return [
            ['123456', true],
            ['123*9-*55sA', false],
        ];
    }

    public function getStrengths(): \Generator
    {
        for ($i = -2; $i < 6; ++$i) {
            yield ['123', $i, $i > 0];
        }
    }

    public function getValidValues(): array
    {
        return [
            ['ABC abc', ['case_diff' => true]],
            ['test', ['email' => true]],
            ['123*9-*55sA', ['min_strength' => StrengthInterface::LEVEL_VERY_WEEK]],
            ['123*9-*55sA', ['min_strength' => StrengthInterface::LEVEL_WEEK]],
            ['123*9-*55sA', ['min_strength' => StrengthInterface::LEVEL_MEDIUM]],
            ['123*9-*55sA', ['min_strength' => StrengthInterface::LEVEL_STRONG]],
            ['123*9-*55sA', ['min_strength' => StrengthInterface::LEVEL_VERY_STRONG]],
            ['abc', ['letters' => true]],
            ['123', ['numbers' => true]],
            ['123*9-*55sA', ['pwned' => true]],
            ['123@', ['special_char' => true]],
        ];
    }

    /**
     * @param mixed|bool $value
     *
     * @dataProvider getConstraints
     */
    public function testEmptyStringIsValid(string $constraint, mixed $value = true): void
    {
        $constraint = $this->createPassword([$constraint => $value]);
        $this->validator->validate('', $constraint);
        $this->assertNoViolation();
    }

    /**
     * @param mixed $value the value to be tested
     *
     * @dataProvider getInvalidValues
     */
    public function testInvalid(mixed $value, array $options, string $message, array $parameters = []): void
    {
        $constraint = $this->createPassword($options);
        $this->validator->validate($value, $constraint);
        $this->buildViolation($message)
            ->setParameters($parameters)
            ->setInvalidValue($value)
            ->assertRaised();
    }

    /**
     * @param mixed|bool $value
     *
     * @dataProvider getConstraints
     */
    public function testNullIsValid(string $constraint, mixed $value = true): void
    {
        $constraint = $this->createPassword([$constraint => $value]);
        $this->validator->validate(null, $constraint);
        $this->assertNoViolation();
    }

    /**
     * @dataProvider getPasswords
     */
    public function testPwned(string $value, bool $violation = true): void
    {
        $options = ['pwned' => true];
        $constraint = $this->createPassword($options);
        $this->validator->validate($value, $constraint);
        if ($violation) {
            $violations = $this->context->getViolations();
            assertSame(1, \count($violations));
            $first = $violations[0];
            assertSame('password.pwned', $first->getMessageTemplate());
            assertSame($value, $first->getInvalidValue());
        } else {
            $this->assertNoViolation();
        }
    }

    /**
     * @dataProvider getStrengths
     */
    public function testStrength(string $value, int $min_strength, bool $violation = true): void
    {
        $options = ['min_strength' => $min_strength];
        $constraint = $this->createPassword($options);
        $this->validator->validate($value, $constraint);

        if ($violation) {
            $parameters = [
                '%minimum%' => self::EMPTY_MESSAGE,
                '%current%' => self::EMPTY_MESSAGE,
            ];
            $this->buildViolation('password.min_strength')
                ->setParameters($parameters)
                ->assertRaised();
        } else {
            $this->assertNoViolation();
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
        $this->assertNoViolation();
    }

    protected function createValidator(): PasswordValidator
    {
        $translator = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $translator->method('trans')
            ->willReturn(self::EMPTY_MESSAGE);

        return new PasswordValidator($translator);
    }

    private function createPassword(array $options): Password
    {
        $options = \array_merge([
            'all' => false,
            'case_diff' => false,
            'email' => false,
            'letters' => false,
            'min_strength' => StrengthInterface::LEVEL_NONE,
            'numbers' => false,
            'pwned' => false,
            'special_char' => false,
        ], $options);

        return new Password($options);
    }
}
