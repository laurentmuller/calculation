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

namespace App\Tests\Form\Type;

use App\Form\Type\CustomColorType;
use App\Tests\Form\PreloadedExtensionsTrait;
use Symfony\Component\Form\Test\TypeTestCase;

final class CustomColorTypeTest extends TypeTestCase
{
    use PreloadedExtensionsTrait;

    public function testFormView(): void
    {
        $view = $this->factory->create(CustomColorType::class)
            ->createView();
        $this->assertSameColors($view->vars);
        $this->assertSameClass($view->vars['attr']);
    }

    #[\Override]
    protected function getPreloadedExtensions(): array
    {
        $colorsPath = __DIR__ . '/../../files/json/colors.json';

        return [
            new CustomColorType($colorsPath),
        ];
    }

    private function assertSameClass(array $attr): void
    {
        self::assertArrayHasKey('class', $attr);
        self::assertSame('color-picker', $attr['class']);
    }

    private function assertSameColors(array $vars): void
    {
        self::assertArrayHasKey('colors', $vars);
        $colors = $vars['colors'];
        self::assertIsArray($colors);
        self::assertCount(2, $colors);
        self::assertArrayHasKey('Black', $colors);
        self::assertSame('#000000', $colors['Black']);
        self::assertArrayHasKey('White', $colors);
        self::assertSame('#FFFFFF', $colors['White']);
    }
}
