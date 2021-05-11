<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Interfaces\StrengthInterface;
use App\Util\FormatUtils;
use App\Validator\Password;
use App\Validator\PasswordValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Unit test for {@link App\Validator\PasswordValidator} class.
 *
 * @author Laurent Muller
 */
class PasswordValidatorTest extends ConstraintValidatorTestCase
{
    private const EMPTY_MESSAGE = 'empty';

    public function getInvalids(): array
    {
        return [
            ['abc', ['casediff' => true], 'password.casediff'],
            ['myemail@website.com', ['email' => true], 'password.email'],
            ['123', ['letters' => true], 'password.letters'],
            ['abc', ['numbers' => true], 'password.numbers'],
            ['123', ['pwned' => true], 'password.pwned', ['{{count}}' => FormatUtils::formatInt(1078184)]],
            ['123', ['specialchar' => true], 'password.specialchar'],
            ['@@@', ['letters' => true, 'numbers' => true], 'password.letters'],
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
            ['123*9-*55sA', ['minstrength' => 0]],
            ['123*9-*55sA', ['minstrength' => 1]],
            ['123*9-*55sA', ['minstrength' => 2]],
            ['123*9-*55sA', ['minstrength' => 3]],
            ['123*9-*55sA', ['minstrength' => 4]],
            ['abc', ['letters' => true]],
            ['123', ['numbers' => true]],
            ['123*9-*55sA', ['pwned' => true]],
            ['123@', ['specialchar' => true]],
        ];
    }

    public function testEmptyStringIsValid(): void
    {
        $constraint = $this->createPassword(['casediff' => true]);
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

    public function testNullIsValid(): void
    {
        $constraint = $this->createPassword(['casediff' => true]);
        $this->validator->validate(null, $constraint);
        $this->assertNoViolation();
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
