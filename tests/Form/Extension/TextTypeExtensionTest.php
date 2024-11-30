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

namespace App\Tests\Form\Extension;

use App\Form\Extension\TextTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\TypeTestCase;

class TextTypeExtensionTest extends TypeTestCase
{
    public function testFormViewWithAllValues(): void
    {
        $keys = [
            'prepend_icon',
            'prepend_title',
            'prepend_class',
            'append_icon',
            'append_title',
            'append_class',
        ];
        $options = \array_combine($keys, $keys);
        $view = $this->factory->create(TextType::class, null, $options)
            ->createView();
        foreach ($keys as $key) {
            self::assertArrayHasKey($key, $view->vars);
            self::assertSame($key, $view->vars[$key]);
        }
    }

    public function testFormViewWithNoValue(): void
    {
        $view = $this->factory->create(TextType::class)
            ->createView();
        self::assertArrayHasKey('attr', $view->vars);
        $attr = $view->vars['attr'];
        self::assertArrayNotHasKey('prepend_icon', $attr);
        self::assertArrayNotHasKey('prepend_title', $attr);
        self::assertArrayNotHasKey('prepend_class', $attr);
        self::assertArrayNotHasKey('append_icon', $attr);
        self::assertArrayNotHasKey('append_title', $attr);
        self::assertArrayNotHasKey('append_class', $attr);
    }

    protected function getTypeExtensions(): array
    {
        return [
            new TextTypeExtension(),
        ];
    }
}
