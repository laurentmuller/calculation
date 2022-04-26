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

use App\Validator\Strength;
use App\Validator\StrengthValidator;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Unit test for {@link App\Validator\StrengthValidator} class.
 */
class StrengthValidatorTest extends ConstraintValidatorTestCase
{
    private const EMPTY_MESSAGE = 'empty';

    public function getStrengthInvalids(): \Generator
    {
        yield [-2];
        yield [5];
    }

    public function getStrengths(): \Generator
    {
        for ($i = -1; $i < 5; ++$i) {
            yield ['123', $i, $i > 0];
        }
    }

    /**
     * @dataProvider getStrengths
     */
    public function testStrength(string $value, int $minstrength, bool $violation = true): void
    {
        $constraint = new Strength($minstrength);
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
     * @dataProvider getStrengthInvalids
     */
    public function testStrengthInvalid(int $minstrength): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Strength($minstrength);
    }

    /**
     * {@inheritDoc}
     */
    protected function createValidator(): StrengthValidator
    {
        $translator = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $translator->method('trans')
            ->willReturn(self::EMPTY_MESSAGE);

        return new StrengthValidator($translator);
    }
}
