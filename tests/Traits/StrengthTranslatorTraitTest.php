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

use App\Traits\StrengthTranslatorTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Unit test for {@link App\Traits\StrengthTranslatorTrait} class.
 */
class StrengthTranslatorTraitTest extends TestCase
{
    use StrengthTranslatorTrait;

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
     * @dataProvider getTranslateLevels
     */
    public function testTranslateLevel(int $level, string $message): void
    {
        $this->translator = $this->getTranslator();
        $actual = $this->translateLevel($level);
        $expected = "password.strength_level.$message";
        $this->assertEquals($actual, $expected);
    }

    private function getTranslator(): TranslatorInterface
    {
        $translator = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $translator->method('trans')
            ->willReturn($this->returnArgument(0));

        return $translator;
    }
}
