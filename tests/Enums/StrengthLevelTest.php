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

/**
 * Unit test for the {@link StrengthLevel} enumeration.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
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
        self::assertEquals($expected, $default);
        $default = PropertyServiceInterface::DEFAULT_STRENGTH_LEVEL;
        self::assertEquals($expected, $default);
    }

    public function testLabel(): void
    {
        self::assertEquals('strength_level.medium', StrengthLevel::MEDIUM->getReadable());
        self::assertEquals('strength_level.none', StrengthLevel::NONE->getReadable());
        self::assertEquals('strength_level.strong', StrengthLevel::STRONG->getReadable());
        self::assertEquals('strength_level.very_strong', StrengthLevel::VERY_STRONG->getReadable());
        self::assertEquals('strength_level.very_weak', StrengthLevel::VERY_WEAK->getReadable());
        self::assertEquals('strength_level.weak', StrengthLevel::WEAK->getReadable());
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
        self::assertEquals($expected, $sorted);
    }

    public function testValue(): void
    {
        self::assertEquals(2, StrengthLevel::MEDIUM->value);
        self::assertEquals(-1, StrengthLevel::NONE->value);
        self::assertEquals(3, StrengthLevel::STRONG->value);
        self::assertEquals(4, StrengthLevel::VERY_STRONG->value);
        self::assertEquals(0, StrengthLevel::VERY_WEAK->value);
        self::assertEquals(1, StrengthLevel::WEAK->value);
    }
}
