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

use App\Enums\StrengthLevel;
use App\Traits\StrengthLevelTranslatorTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Unit test for {@link StrengthLevelTranslatorTrait} class.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class StrengthLevelTranslatorTraitTest extends TestCase
{
    use StrengthLevelTranslatorTrait;

    private ?TranslatorInterface $translator;

    public function getTranslateLevels(): array
    {
        return [
            [-2, 'none'],
            [-1, 'none'],
            [0, 'very_weak'],
            [1, 'weak'],
            [2, 'medium'],
            [3, 'strong'],
            [4, 'very_strong'],
            [5, 'very_strong'],
        ];
    }

    /**
     * @psalm-suppress InvalidNullableReturnType
     * @psalm-suppress NullableReturnStatement
     */
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * @dataProvider getTranslateLevels
     */
    public function testTranslateLevel(int $value, string $message): void
    {
        $expected = "strength_level.$message";
        $this->translator = $this->createTranslator($expected);
        $level = StrengthLevel::tryFrom($value) ?? StrengthLevel::NONE;
        $actual = $this->translateLevel($level);
        self::assertEquals($actual, $expected);
    }

    private function createTranslator(string $message): TranslatorInterface
    {
        $translator = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $translator->method('trans')
            ->willReturn($message);

        return $translator;
    }
}
