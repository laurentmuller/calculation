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

use App\Constraint\AbstractConstraintValidator;
use App\Constraint\Password;
use App\Constraint\Strength;
use App\Tests\Fixture\FixtureStringable;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<AbstractConstraintValidator>
 */
#[AllowMockObjectsWithoutExpectations]
final class AbstractConstraintValidatorTest extends ConstraintValidatorTestCase
{
    public function testEmptyIsValid(): void
    {
        $constraint = new Password();
        $this->validator->validate('', $constraint);
        self::assertNoViolation();
    }

    public function testEmptyStringable(): void
    {
        $constraint = new Password();
        $this->validator->validate(new FixtureStringable(), $constraint);
        self::assertNoViolation();
    }

    public function testInvalidClass(): void
    {
        $constraint = new Strength();
        self::expectException(UnexpectedTypeException::class);
        $this->validator->validate('', $constraint);
    }

    public function testInvalidObject(): void
    {
        $constraint = new Password();
        self::expectException(UnexpectedTypeException::class);
        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testNullIsValid(): void
    {
        $constraint = new Password();
        $this->validator->validate(null, $constraint);
        self::assertNoViolation();
    }

    public function testStringIsValid(): void
    {
        $constraint = new Password();
        $this->validator->validate('password', $constraint);
        self::assertNoViolation();
    }

    /**
     * @phpstan-ignore missingType.generics
     */
    #[\Override]
    protected function createValidator(): AbstractConstraintValidator
    {
        return new class extends AbstractConstraintValidator {
            public function __construct()
            {
                parent::__construct(Password::class);
            }

            #[\Override]
            protected function doValidate(string $value, Constraint $constraint): void
            {
            }
        };
    }
}
