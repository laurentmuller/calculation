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
use Symfony\Component\Form\Test\TypeTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(StrengthLevel::class)]
class StrengthLevelTest extends TypeTestCase
{
    public function testCount(): void
    {
        self::assertCount(6, StrengthLevel::cases());
        self::assertCount(6, StrengthLevel::sorted());
    }

    public function testDefault(): void
    {
        $expected = StrengthLevel::NONE;
        $default = StrengthLevel::getDefault();
        self::assertSame($expected, $default);
        $default = PropertyServiceInterface::DEFAULT_STRENGTH_LEVEL;
        self::assertSame($expected, $default); // @phpstan-ignore-line
    }

    public function testLabel(): void
    {
        self::assertSame('strength_level.medium', StrengthLevel::MEDIUM->getReadable());
        self::assertSame('strength_level.none', StrengthLevel::NONE->getReadable());
        self::assertSame('strength_level.strong', StrengthLevel::STRONG->getReadable());
        self::assertSame('strength_level.very_strong', StrengthLevel::VERY_STRONG->getReadable());
        self::assertSame('strength_level.very_weak', StrengthLevel::VERY_WEAK->getReadable());
        self::assertSame('strength_level.weak', StrengthLevel::WEAK->getReadable());
    }

    public function testPercent(): void
    {
        self::assertSame(0, StrengthLevel::NONE->percent());
        self::assertSame(20, StrengthLevel::VERY_WEAK->percent());
        self::assertSame(40, StrengthLevel::WEAK->percent());
        self::assertSame(60, StrengthLevel::MEDIUM->percent());
        self::assertSame(80, StrengthLevel::STRONG->percent());
        self::assertSame(100, StrengthLevel::VERY_STRONG->percent());
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
        $sorted = StrengthLevel::sorted();
        self::assertSame($expected, $sorted);
    }

    public function testValue(): void
    {
        self::assertSame(-1, StrengthLevel::NONE->value); // @phpstan-ignore-line
        self::assertSame(0, StrengthLevel::VERY_WEAK->value); // @phpstan-ignore-line
        self::assertSame(1, StrengthLevel::WEAK->value); // @phpstan-ignore-line
        self::assertSame(2, StrengthLevel::MEDIUM->value); // @phpstan-ignore-line
        self::assertSame(3, StrengthLevel::STRONG->value); // @phpstan-ignore-line
        self::assertSame(4, StrengthLevel::VERY_STRONG->value); // @phpstan-ignore-line
    }
}
