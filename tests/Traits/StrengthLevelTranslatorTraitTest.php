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
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(StrengthLevelTranslatorTrait::class)]
class StrengthLevelTranslatorTraitTest extends TestCase
{
    use StrengthLevelTranslatorTrait;

    private ?TranslatorInterface $translator = null;

    public static function getTranslateLevels(): \Iterator
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

    /**
     * @throws Exception
     */
    public function getTranslator(): TranslatorInterface
    {
        if (!$this->translator instanceof TranslatorInterface) {
            $this->translator = $this->createTranslator();
        }

        return $this->translator;
    }

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getTranslateLevels')]
    public function testTranslateLevel(int $value, string $message): void
    {
        $expected = "strength_level.$message";
        $this->translator = $this->createTranslator($expected);
        $level = StrengthLevel::tryFrom($value) ?? StrengthLevel::NONE;
        $actual = $this->translateLevel($level);
        self::assertSame($actual, $expected);
    }

    /**
     * @throws Exception
     */
    private function createTranslator(string $message = ''): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturn($message);

        return $translator;
    }
}
