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

use App\Form\Extension\InputGroupTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\TypeTestCase;

final class InputGroupTypeExtensionTest extends TypeTestCase
{
    private const KEYS = [
        'prepend_icon',
        'prepend_title',
        'prepend_class',
        'append_icon',
        'append_title',
        'append_class',
    ];

    public function testFormViewWithAllValues(): void
    {
        $options = \array_combine(self::KEYS, self::KEYS);
        $view = $this->createTextView($options);
        foreach (self::KEYS as $key) {
            self::assertArrayHasKey($key, $view->vars);
            self::assertSame($key, $view->vars[$key]);
        }
    }

    public function testFormViewWithNoValue(): void
    {
        $view = $this->createTextView();
        self::assertArrayHasKey('attr', $view->vars);
        $attributes = $view->vars['attr'];
        foreach (self::KEYS as $key) {
            self::assertArrayNotHasKey($key, $view->vars);
            self::assertArrayNotHasKey($key, $attributes);
        }
    }

    /**
     * @return InputGroupTypeExtension[]
     */
    #[\Override]
    protected function getTypeExtensions(): array
    {
        return [new InputGroupTypeExtension()];
    }

    private function createTextView(array $options = []): FormView
    {
        return $this->factory->create(type: TextType::class, options: $options)
            ->createView();
    }
}
