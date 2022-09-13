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

use App\Enums\StrengthLevel;
use App\Validator\Strength;
use App\Validator\StrengthValidator;
use Createnl\ZxcvbnBundle\ZxcvbnFactoryInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZxcvbnPhp\Zxcvbn;

/**
 * Unit test for {@link StrengthValidator} class.
 *
 * @extends ConstraintValidatorTestCase<StrengthValidator>
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class StrengthValidatorTest extends ConstraintValidatorTestCase
{
    private const EMPTY_MESSAGE = 'empty';

    public function getStrengthInvalids(): \Generator
    {
        yield [-2];
        yield [5];
    }

    public function getStrengthLevels(): \Generator
    {
        $levels = StrengthLevel::cases();
        foreach ($levels as $level) {
            yield [$level];
        }
    }

    public function getStrengths(): \Generator
    {
        for ($i = -1; $i < 5; ++$i) {
            yield ['123', $i, $i > 0];
        }
    }

    /**
     * @dataProvider getStrengthLevels
     */
    public function testEmptyStringIsValid(StrengthLevel $level): void
    {
        $constraint = new Strength($level);
        $this->validator->validate('', $constraint);
        self::assertNoViolation();
    }

    /**
     * @dataProvider getStrengthLevels
     */
    public function testNullIsValid(StrengthLevel $level): void
    {
        $constraint = new Strength($level);
        $this->validator->validate(null, $constraint);
        self::assertNoViolation();
    }

    /**
     * @dataProvider getStrengths
     */
    public function testStrength(string $value, int $strength, bool $violation = true): void
    {
        $level = StrengthLevel::tryFrom($strength) ?? StrengthLevel::NONE;
        $constraint = new Strength($level);
        $this->validator->validate($value, $constraint);

        if ($violation) {
            $parameters = [
                '%minimum%' => self::EMPTY_MESSAGE,
                '%score%' => self::EMPTY_MESSAGE,
            ];
            $this->buildViolation('password.strength_level')
                ->setCode(Strength::IS_STRENGTH_ERROR)
                ->setParameters($parameters)
                ->assertRaised();
        } else {
            self::assertNoViolation();
        }
    }

    /**
     * @dataProvider getStrengthInvalids
     */
    public function testStrengthInvalid(int $strength): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Strength($strength);
    }

    protected function createValidator(): StrengthValidator
    {
        $translator = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $translator->method('trans')
            ->willReturn(self::EMPTY_MESSAGE);

        $factory = $this->getMockBuilder(ZxcvbnFactoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $factory->method('createZxcvbn')
            ->willReturn(new Zxcvbn());

        return new StrengthValidator($translator, $factory);
    }
}
