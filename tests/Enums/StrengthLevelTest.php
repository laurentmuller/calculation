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

namespace App\Tests\Enums;

use App\Enums\StrengthLevel;
use App\Interfaces\PropertyServiceInterface;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StrengthLevelTest extends TestCase
{
    use TranslatorMockTrait;

    /**
     * @phpstan-return \Generator<int, array{StrengthLevel, StrengthLevel}>
     */
    public static function getDefault(): \Generator
    {
        yield [StrengthLevel::getDefault(), StrengthLevel::NONE];
        yield [PropertyServiceInterface::DEFAULT_STRENGTH_LEVEL, StrengthLevel::NONE];
    }

    /**
     * @phpstan-return \Generator<int, array{string, StrengthLevel}>
     */
    public static function getLabels(): \Generator
    {
        yield ['strength_level.medium', StrengthLevel::MEDIUM];
        yield ['strength_level.none', StrengthLevel::NONE];
        yield ['strength_level.strong', StrengthLevel::STRONG];
        yield ['strength_level.very_strong', StrengthLevel::VERY_STRONG];
        yield ['strength_level.very_weak', StrengthLevel::VERY_WEAK];
        yield ['strength_level.weak', StrengthLevel::WEAK];
    }

    /**
     * @phpstan-return \Generator<int, array{int, StrengthLevel}>
     */
    public static function getPercents(): \Generator
    {
        yield [0, StrengthLevel::NONE];
        yield [20, StrengthLevel::VERY_WEAK];
        yield [40, StrengthLevel::WEAK];
        yield [60, StrengthLevel::MEDIUM];
        yield [80, StrengthLevel::STRONG];
        yield [100, StrengthLevel::VERY_STRONG];
    }

    /**
     * @phpstan-return \Generator<int, array{StrengthLevel, StrengthLevel|int, bool}>
     */
    public static function getSmallerValues(): \Generator
    {
        yield [StrengthLevel::NONE, StrengthLevel::VERY_WEAK, true];
        yield [StrengthLevel::VERY_WEAK, StrengthLevel::WEAK, true];
        yield [StrengthLevel::WEAK, StrengthLevel::MEDIUM, true];
        yield [StrengthLevel::MEDIUM, StrengthLevel::STRONG, true];
        yield [StrengthLevel::STRONG, StrengthLevel::VERY_STRONG, true];

        yield [StrengthLevel::VERY_WEAK, StrengthLevel::NONE, false];
        yield [StrengthLevel::VERY_WEAK, -1, false];
    }

    /**
     * @phpstan-return \Generator<int, array{StrengthLevel, int}>
     */
    public static function getValues(): \Generator
    {
        yield [StrengthLevel::NONE, -1];
        yield [StrengthLevel::VERY_WEAK, 0];
        yield [StrengthLevel::WEAK, 1];
        yield [StrengthLevel::MEDIUM, 2];
        yield [StrengthLevel::STRONG, 3];
        yield [StrengthLevel::VERY_STRONG, 4];
    }

    public function testCount(): void
    {
        $expected = 6;
        self::assertCount($expected, StrengthLevel::cases());
        self::assertCount($expected, StrengthLevel::sorted());
    }

    #[DataProvider('getDefault')]
    public function testDefault(StrengthLevel $value, StrengthLevel $expected): void
    {
        self::assertSame($expected, $value);
    }

    #[DataProvider('getLabels')]
    public function testLabel(string $expected, StrengthLevel $level): void
    {
        $actual = $level->getReadable();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getPercents')]
    public function testPercent(int $expected, StrengthLevel $level): void
    {
        self::assertSame($expected, $level->percent());
    }

    #[DataProvider('getSmallerValues')]
    public function testSmaller(StrengthLevel $level, int|StrengthLevel $other, bool $expected): void
    {
        $actual = $level->isSmaller($other);
        self::assertSame($expected, $actual);
    }

    public function testSorted(): void
    {
        $expected = [
            StrengthLevel::NONE,
            StrengthLevel::VERY_WEAK,
            StrengthLevel::WEAK,
            StrengthLevel::MEDIUM,
            StrengthLevel::STRONG,
            StrengthLevel::VERY_STRONG,
        ];
        $actual = StrengthLevel::sorted();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getLabels')]
    public function testTranslate(string $expected, StrengthLevel $level): void
    {
        $translator = $this->createMockTranslator();
        $actual = $level->trans($translator);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getValues')]
    public function testValue(StrengthLevel $level, int $expected): void
    {
        $actual = $level->value;
        self::assertSame($expected, $actual);
    }

    public function testValues(): void
    {
        $expected = \range(-1, 4);
        $actual = StrengthLevel::values();
        self::assertSame($expected, $actual);
    }
}
