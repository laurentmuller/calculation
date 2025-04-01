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

namespace App\Tests\Traits;

use App\Constraint\Strength;
use App\Enums\StrengthLevel;
use App\Tests\TranslatorMockTrait;
use App\Traits\StrengthLevelTranslatorTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class StrengthLevelTranslatorTraitTest extends TestCase
{
    use StrengthLevelTranslatorTrait;
    use TranslatorMockTrait;

    private TranslatorInterface $translator;

    #[\Override]
    protected function setUp(): void
    {
        $this->translator = $this->createMockTranslator();
    }

    /**
     * @psalm-return \Generator<array-key, array{int, string}>
     */
    public static function getTranslateLevels(): \Generator
    {
        yield [-2, 'none'];
        yield [-1, 'none'];
        yield [0, 'very_weak'];
        yield [1, 'weak'];
        yield [2, 'medium'];
        yield [3, 'strong'];
        yield [4, 'very_strong'];
        yield [5, 'very_strong'];
    }

    #[\Override]
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * @psalm-suppress InternalClass
     * @psalm-suppress InternalMethod
     */
    public function testAddStrengthLevelViolation(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $root = new \stdClass();
        $translator = $this->getTranslator();
        $context = new ExecutionContext($validator, $root, $translator);
        $constraint = new Strength();
        $minimum = StrengthLevel::MEDIUM;
        $score = StrengthLevel::VERY_WEAK;
        $this->addStrengthLevelViolation(
            $context,
            $constraint,
            $minimum,
            $score
        );
        self::assertCount(1, $context->getViolations());
    }

    public function testTranslateInvalidLevel(): void
    {
        $actual = $this->translateInvalidLevel(-10);
        self::assertStringContainsString('password.strength_invalid', $actual);

        $actual = $this->translateInvalidLevel(StrengthLevel::NONE);
        self::assertStringContainsString('password.strength_invalid', $actual);
    }

    #[DataProvider('getTranslateLevels')]
    public function testTranslateLevel(int $value, string $message): void
    {
        $expected = "strength_level.$message";
        $this->translator = $this->createMockTranslator($expected);
        $level = StrengthLevel::tryFrom($value) ?? StrengthLevel::NONE;
        $actual = $this->translateLevel($level);
        self::assertSame($actual, $expected);
    }

    public function testTranslateScore(): void
    {
        $actual = $this->translateScore(StrengthLevel::VERY_STRONG, StrengthLevel::VERY_WEAK);
        self::assertStringContainsString('password.strength_level', $actual);
    }
}
