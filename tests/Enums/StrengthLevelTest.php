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
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(StrengthLevel::class)]
class StrengthLevelTest extends TestCase
{
    public static function getDefault(): array
    {
        return [
            [StrengthLevel::getDefault(), StrengthLevel::NONE],
            [PropertyServiceInterface::DEFAULT_STRENGTH_LEVEL, StrengthLevel::NONE],
        ];
    }

    public static function getLabels(): array
    {
        return [
            ['strength_level.medium', StrengthLevel::MEDIUM],
            ['strength_level.none', StrengthLevel::NONE],
            ['strength_level.strong', StrengthLevel::STRONG],
            ['strength_level.very_strong', StrengthLevel::VERY_STRONG],
            ['strength_level.very_weak', StrengthLevel::VERY_WEAK],
            ['strength_level.weak', StrengthLevel::WEAK],
        ];
    }

    public static function getValues(): array
    {
        return [
            [StrengthLevel::NONE, -1],
            [StrengthLevel::VERY_WEAK, 0],
            [StrengthLevel::WEAK, 1],
            [StrengthLevel::MEDIUM, 2],
            [StrengthLevel::STRONG, 3],
            [StrengthLevel::VERY_STRONG, 4],
        ];
    }

    public function testCount(): void
    {
        self::assertCount(6, StrengthLevel::cases());
        self::assertCount(6, StrengthLevel::sorted());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getDefault')]
    public function testDefault(StrengthLevel $value, StrengthLevel $expected): void
    {
        self::assertSame($expected, $value);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getLabels')]
    public function testLabel(string $expected, StrengthLevel $level): void
    {
        self::assertSame($expected, $level->getReadable());
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

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getLabels')]
    public function testTranslate(string $expected, StrengthLevel $level): void
    {
        $translator = $this->createTranslator();
        self::assertSame($expected, $level->trans($translator));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getValues')]
    public function testValue(StrengthLevel $level, int $expected): void
    {
        self::assertSame($expected, $level->value);
    }

    /**
     * @throws Exception
     */
    private function createTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnArgument(0);

        return $translator;
    }
}
