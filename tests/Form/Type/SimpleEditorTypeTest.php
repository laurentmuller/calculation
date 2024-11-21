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
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SimpleEditorTypeTest extends TypeTestCase
{
    use PreloadedExtensionsTrait;

    public function testFormView(): void
    {
        $view = $this->factory->create(SimpleEditorType::class)
            ->createView();
        self::assertArrayHasKey('id', $view->vars);
    }

    /**
     * @throws Exception
     */
    public function testInvalidJson(): void
    {
        $actionsPath = __DIR__ . '/../../Data/json/fontawesome_invalid.json';

        $resolver = new OptionsResolver();
        $view = $this->createMock(FormView::class);
        $form = $this->createMock(FormInterface::class);
        $options = ['required' => false];

        $editor = new SimpleEditorType($actionsPath);
        $editor->configureOptions($resolver);
        $editor->buildView($view, $form, $options);
        self::assertSame(HiddenType::class, $editor->getParent());
    }

    public function testNoAction(): void
    {
        $options = ['actions' => []];
        $view = $this->factory->create(SimpleEditorType::class, options: $options)
            ->createView();
        self::assertArrayHasKey('id', $view->vars);
    }

    public function testSubmit(): void
    {
        $form = $this->factory->create(SimpleEditorType::class);
        $form->submit('Fake Text for testing purpose.');
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isValid());
    }

    protected function getPreloadedExtensions(): array
    {
        $actionsPath = __DIR__ . '/../../../resources/data/simple_editor_actions.json';

        return [new SimpleEditorType($actionsPath)];
    }
}
