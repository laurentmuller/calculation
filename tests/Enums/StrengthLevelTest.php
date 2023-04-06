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
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(StrengthLevel::class)]
class StrengthLevelTest extends TypeTestCase
{
    private ?TranslatorInterface $translator = null;

    public static function getLabel(): array
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

    #[\PHPUnit\Framework\Attributes\DataProvider('getLabel')]
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
    #[\PHPUnit\Framework\Attributes\DataProvider('getLabel')]
    public function testTranslate(string $expected, StrengthLevel $level): void
    {
        $translator = $this->createTranslator();
        self::assertSame($expected, $level->trans($translator));
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

    /**
     * @throws Exception
     */
    private function createTranslator(): TranslatorInterface
    {
        if (!$this->translator instanceof TranslatorInterface) {
            $this->translator = $this->createMock(TranslatorInterface::class);
            $this->translator->method('trans')
                ->willReturnArgument(0);
        }

        return $this->translator;
    }
}
