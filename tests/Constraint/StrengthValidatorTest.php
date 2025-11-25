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

use App\Constraint\Strength;
use App\Constraint\StrengthValidator;
use App\Enums\StrengthLevel;
use App\Tests\TranslatorMockTrait;
use Createnl\ZxcvbnBundle\ZxcvbnFactoryInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use ZxcvbnPhp\Zxcvbn;

/**
 * @extends ConstraintValidatorTestCase<StrengthValidator>
 */
final class StrengthValidatorTest extends ConstraintValidatorTestCase
{
    use TranslatorMockTrait;

    private const EMPTY_MESSAGE = 'empty';

    public static function getStrengthInvalids(): \Generator
    {
        yield [-2];
        yield [5];
    }

    public static function getStrengthLevels(): \Generator
    {
        $levels = StrengthLevel::cases();
        foreach ($levels as $level) {
            yield [$level];
            yield [$level->value];
        }
    }

    public static function getStrengths(): \Generator
    {
        for ($i = -1; $i < 5; ++$i) {
            yield ['123', $i, $i > 0];
        }
    }

    #[DataProvider('getStrengthLevels')]
    public function testEmptyIsValid(StrengthLevel|int $level): void
    {
        $constraint = new Strength($level);
        $this->validator->validate('', $constraint);
        self::assertNoViolation();
    }

    public function testInvalidPath(): void
    {
        $object = new \stdClass();
        $object->user = 'user';
        $object->password = 'password';

        $constraint = new Strength(
            minimum: StrengthLevel::VERY_WEAK,
            userNamePath: 'invalidPath'
        );
        $this->setObject($object);
        self::expectException(ConstraintDefinitionException::class);
        $this->validator->validate($object->password, $constraint);
    }

    #[DataProvider('getStrengthLevels')]
    public function testNullIsValid(StrengthLevel|int $level): void
    {
        $constraint = new Strength($level);
        $this->validator->validate(null, $constraint);
        self::assertNoViolation();
    }

    public function testPaths(): void
    {
        $object = new \stdClass();
        $object->user = 'user';
        $object->email = 'user@fake.com';
        $object->password = 'password';

        $constraint = new Strength(
            minimum: StrengthLevel::VERY_WEAK,
            userNamePath: 'user',
            emailPath: 'email'
        );
        $this->setObject($object);
        $this->validator->validate($object->password, $constraint);
        self::assertNoViolation();
    }

    #[DataProvider('getStrengths')]
    public function testStrength(string $value, int $strength, bool $violation): void
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

    #[DataProvider('getStrengthInvalids')]
    public function testStrengthInvalid(int $strength): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('Unable to find a strength level for the value ' . $strength . '.');
        new Strength($strength);
    }

    #[\Override]
    protected function createValidator(): StrengthValidator
    {
        $factory = $this->createMock(ZxcvbnFactoryInterface::class);
        $factory->method('createZxcvbn')
            ->willReturn(new Zxcvbn());
        $translator = $this->createMockTranslator(self::EMPTY_MESSAGE);

        return new StrengthValidator($translator, $factory);
    }
}
