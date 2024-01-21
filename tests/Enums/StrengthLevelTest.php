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
    public static function getDefault(): \Iterator
    {
        yield [StrengthLevel::getDefault(), StrengthLevel::NONE];
        yield [PropertyServiceInterface::DEFAULT_STRENGTH_LEVEL, StrengthLevel::NONE];
    }

    public static function getLabels(): \Iterator
    {
        yield ['strength_level.medium', StrengthLevel::MEDIUM];
        yield ['strength_level.none', StrengthLevel::NONE];
        yield ['strength_level.strong', StrengthLevel::STRONG];
        yield ['strength_level.very_strong', StrengthLevel::VERY_STRONG];
        yield ['strength_level.very_weak', StrengthLevel::VERY_WEAK];
        yield ['strength_level.weak', StrengthLevel::WEAK];
    }

    public static function getValues(): \Iterator
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

    #[\PHPUnit\Framework\Attributes\DataProvider('getDefault')]
    public function testDefault(StrengthLevel $value, StrengthLevel $expected): void
    {
        self::assertSame($expected, $value);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getLabels')]
    public function testLabel(string $expected, StrengthLevel $level): void
    {
        $actual = $level->getReadable();
        self::assertSame($expected, $actual);
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
        $actual = StrengthLevel::sorted();
        self::assertSame($expected, $actual);
    }

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getLabels')]
    public function testTranslate(string $expected, StrengthLevel $level): void
    {
        $translator = $this->createTranslator();
        $actual = $level->trans($translator);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getValues')]
    public function testValue(StrengthLevel $level, int $expected): void
    {
        $actual = $level->value;
        self::assertSame($expected, $actual);
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
