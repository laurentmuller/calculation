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

use App\Form\Type\SimpleEditorType;
use App\Tests\Form\PreloadedExtensionsTrait;
use Symfony\Component\Form\Test\TypeTestCase;

class SimpleEditorTypeTest extends TypeTestCase
{
    use PreloadedExtensionsTrait;

    public function testFormView(): void
    {
        $view = $this->factory->create(SimpleEditorType::class)
            ->createView();
        self::assertArrayHasKey('id', $view->vars);
    }

    public function testSubmit(): void
    {
        $form = $this->factory->create(SimpleEditorType::class);
        $form->submit('Fake Text for testing purpose.');
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
    }

    protected function getPreloadedExtensions(): array
    {
        $path = __DIR__ . '/../../../resources/data/simple_editor_actions.json';

        return [
            new SimpleEditorType($path),
        ];
    }
}
