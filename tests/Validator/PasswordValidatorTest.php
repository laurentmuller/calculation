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
 * Unit test for {@link App\Validator\PasswordValidator} class.
 */
class PasswordValidatorTest extends ConstraintValidatorTestCase
{
    private const EMPTY_MESSAGE = 'empty';

    public function getConstraints(): array
    {
        return [
            ['casediff'],
            ['email'],
            ['letters'],
            ['numbers'],
            ['specialchar'],
            ['pwned'],
            ['minstrength', StrengthInterface::LEVEL_VERY_STRONG],
        ];
    }

    public function getInvalids(): array
    {
        return [
            ['abc', ['casediff' => true], 'password.casediff'],
            ['myemail@website.com', ['email' => true], 'password.email'],
            ['123', ['letters' => true], 'password.letters'],
            ['abc', ['numbers' => true], 'password.numbers'],
            ['123', ['specialchar' => true], 'password.specialchar'],
            ['@@@', ['letters' => true, 'numbers' => true], 'password.letters'],
        ];
    }

    public function getPwneds(): array
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

    public function getValids(): array
    {
        return [
            ['ABCabc', ['casediff' => true]],
            ['test', ['email' => true]],
            ['123*9-*55sA', ['minstrength' => StrengthInterface::LEVEL_VERY_WEEK]],
            ['123*9-*55sA', ['minstrength' => StrengthInterface::LEVEL_WEEK]],
            ['123*9-*55sA', ['minstrength' => StrengthInterface::LEVEL_MEDIUM]],
            ['123*9-*55sA', ['minstrength' => StrengthInterface::LEVEL_STRONG]],
            ['123*9-*55sA', ['minstrength' => StrengthInterface::LEVEL_VERY_STRONG]],
            ['abc', ['letters' => true]],
            ['123', ['numbers' => true]],
            ['123*9-*55sA', ['pwned' => true]],
            ['123@', ['specialchar' => true]],
        ];
    }

    /**
     * @param mixed $value
     *
     * @dataProvider getConstraints
     */
    public function testEmptyStringIsValid(string $constraint, $value = true): void
    {
        $constraint = $this->createPassword([$constraint => $value]);
        $this->validator->validate('', $constraint);
        $this->assertNoViolation();
    }

    /**
     * @param mixed $value the value to be tested
     *
     * @dataProvider getInvalids
     */
    public function testInvalid($value, array $options, string $message, array $parameters = []): void
    {
        $constraint = $this->createPassword($options);
        $this->validator->validate($value, $constraint);
        $this->buildViolation($message)
            ->setParameters($parameters)
            ->setInvalidValue($value)
            ->assertRaised();
    }

    /**
     * @param mixed $value
     *
     * @dataProvider getConstraints
     */
    public function testNullIsValid(string $constraint, $value = true): void
    {
        $constraint = $this->createPassword([$constraint => $value]);
        $this->validator->validate(null, $constraint);
        $this->assertNoViolation();
    }

    /**
     * @dataProvider getPwneds
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
    public function testStrength(string $value, int $minstrength, bool $violation = true): void
    {
        $options = ['minstrength' => $minstrength];
        $constraint = $this->createPassword($options);
        $this->validator->validate($value, $constraint);

        if ($violation) {
            $parameters = [
                '{{strength_min}}' => self::EMPTY_MESSAGE,
                '{{strength_current}}' => self::EMPTY_MESSAGE,
            ];
            $this->buildViolation('password.minstrength')
                ->setParameters($parameters)
                ->setInvalidValue($value)
                ->assertRaised();
        } else {
            $this->assertNoViolation();
        }
    }

    /**
     * @param mixed $value the value to be tested
     *
     * @dataProvider getValids
     */
    public function testValid($value, array $options): void
    {
        $constraint = $this->createPassword($options);
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    /**
     * {@inheritDoc}
     */
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
            'casediff' => false,
            'email' => false,
            'letters' => false,
            'minstrength' => StrengthInterface::LEVEL_NONE,
            'numbers' => false,
            'pwned' => false,
            'specialchar' => false,
        ], $options);

        return new Password($options);
    }
}
