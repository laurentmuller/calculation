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

use App\Validator\AbstractConstraintValidator;
use App\Validator\Password;
use App\Validator\Strength;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<AbstractConstraintValidator>
 */
#[CoversClass(AbstractConstraintValidator::class)]
class AbstractConstraintValidatorTest extends ConstraintValidatorTestCase
{
    public function testEmptyIsValid(): void
    {
        $constraint = new Password();
        $this->validator->validate('', $constraint);
        self::assertNoViolation();
    }

    public function testInvalidClass(): void
    {
        $constraint = new Strength();
        self::expectException(UnexpectedTypeException::class);
        $this->validator->validate('', $constraint);
        self::fail('An unexpected type exception must be raised');
    }

    public function testInvalidObject(): void
    {
        $constraint = new Password();
        self::expectException(UnexpectedTypeException::class);
        $this->validator->validate(new \stdClass(), $constraint);
        self::fail('An unexpected type exception must be raised');
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
     * @psalm-suppress MissingTemplateParam
     *
     * @phpstan-ignore-next-line
     */
    protected function createValidator(): AbstractConstraintValidator
    {
        return new class() extends AbstractConstraintValidator {
            public function __construct()
            {
                $className = Password::class;
                parent::__construct($className);
            }

            protected function doValidate(string $value, Constraint $constraint): void
            {
            }
        };
    }
}
