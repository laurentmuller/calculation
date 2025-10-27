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

use App\Form\Extension\FileTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

final class FileTypeExtensionTest extends TypeTestCase
{
    public function testFormViewWithMaxFiles(): void
    {
        $options = ['maxfiles' => 10];
        $view = $this->createView($options);
        $attributes = $view->vars['attr'];
        self::assertArrayHasKey('maxfiles', $attributes);
        self::assertSame(10, $attributes['maxfiles']);
    }

    public function testFormViewWithMaxFilesOne(): void
    {
        $options = ['maxfiles' => 1];
        $view = $this->createView($options);
        $attributes = $view->vars['attr'];
        self::assertArrayNotHasKey('maxfiles', $attributes);
    }

    public function testFormViewWithMaxFilesZero(): void
    {
        $options = ['maxfiles' => 0];
        $view = $this->createView($options);
        $attributes = $view->vars['attr'];
        self::assertArrayNotHasKey('maxfiles', $attributes);
    }

    public function testFormViewWithMaxSize(): void
    {
        $options = ['maxsize' => '10'];
        $view = $this->createView($options);
        $attributes = $view->vars['attr'];
        self::assertArrayHasKey('maxsize', $attributes);
        self::assertSame(10, $attributes['maxsize']);
    }

    public function testFormViewWithMaxSizeEmpty(): void
    {
        $options = ['maxsize' => ''];
        $view = $this->createView($options);
        $attributes = $view->vars['attr'];
        self::assertArrayNotHasKey('maxsize', $attributes);
    }

    public function testFormViewWithMaxSizeInKb(): void
    {
        $options = ['maxsize' => '1k'];
        $view = $this->createView($options);
        $attributes = $view->vars['attr'];
        self::assertArrayHasKey('maxsize', $attributes);
        self::assertSame(1_000, $attributes['maxsize']);
    }

    public function testFormViewWithMaxSizeInvalid(): void
    {
        self::expectException(InvalidOptionsException::class);
        $options = ['maxsize' => 'fake'];
        $this->createView($options);
    }

    public function testFormViewWithMaxSizeTotal(): void
    {
        $options = ['maxsizetotal' => '10'];
        $view = $this->createView($options);
        $attributes = $view->vars['attr'];
        self::assertArrayHasKey('maxsizetotal', $attributes);
        self::assertSame(10, $attributes['maxsizetotal']);
    }

    public function testFormViewWithMaxSizeTotalEmpty(): void
    {
        $options = ['maxsizetotal' => ''];
        $view = $this->createView($options);
        $attributes = $view->vars['attr'];
        self::assertArrayNotHasKey('maxsizetotal', $attributes);
    }

    public function testFormViewWithMaxSizeTotalInKb(): void
    {
        $options = ['maxsizetotal' => '1k'];
        $view = $this->createView($options);
        $attributes = $view->vars['attr'];
        self::assertArrayHasKey('maxsizetotal', $attributes);
        self::assertSame(1_000, $attributes['maxsizetotal']);
    }

    public function testFormViewWithMaxSizeTotalZero(): void
    {
        $options = ['maxsizetotal' => 0];
        $view = $this->createView($options);
        $attributes = $view->vars['attr'];
        self::assertArrayNotHasKey('maxsizetotal', $attributes);
    }

    public function testFormViewWithMaxSizeZero(): void
    {
        $options = ['maxsize' => 0];
        $view = $this->createView($options);
        $attributes = $view->vars['attr'];
        self::assertArrayNotHasKey('maxsize', $attributes);
    }

    public function testFormViewWithNoOption(): void
    {
        $view = $this->createView();
        $attributes = $view->vars['attr'];
        self::assertArrayNotHasKey('maxfiles', $attributes);
        self::assertArrayNotHasKey('maxsize', $attributes);
        self::assertArrayNotHasKey('maxsizetotal', $attributes);
        self::assertArrayHasKey('placeholder', $attributes);
        self::assertSame('filetype.placeholder', $attributes['placeholder']);
    }

    public function testFormWithMaxFile(): void
    {
        $form = $this->createForm(['maxfiles' => 1]);
        $options = $form->getConfig()
            ->getOptions();
        self::assertArrayHasKey('maxfiles', $options);
        self::assertSame(1, $options['maxfiles']);
    }

    public function testFormWithMaxSize(): void
    {
        $form = $this->createForm(['maxsize' => 1]);
        $options = $form->getConfig()
            ->getOptions();
        self::assertArrayHasKey('maxsize', $options);
    }

    public function testFormWithMaxSizeTotal(): void
    {
        $form = $this->createForm(['maxsizetotal' => 1]);
        $options = $form->getConfig()
            ->getOptions();
        self::assertArrayHasKey('maxsizetotal', $options);
    }

    public function testFormWithNoOption(): void
    {
        $form = $this->createForm();
        $options = $form->getConfig()
            ->getOptions();
        self::assertArrayNotHasKey('maxfiles', $options);
        self::assertArrayNotHasKey('maxsize', $options);
        self::assertArrayNotHasKey('maxsizetotal', $options);
        self::assertArrayHasKey('placeholder', $options);
        self::assertSame('filetype.placeholder', $options['placeholder']);
    }

    public function testUpdateOptions(): void
    {
        $view = $this->factory->createBuilder()
            ->add('file', FileType::class)
            ->getForm()
            ->createView();
        self::assertNotNull($view['file']);
    }

    /**
     * @return FileTypeExtension[]
     */
    #[\Override]
    protected function getTypeExtensions(): array
    {
        return [
            new FileTypeExtension(),
        ];
    }

    /**
     * @phpstan-return FormInterface<mixed>
     */
    private function createForm(array $options = []): FormInterface
    {
        return $this->factory->create(FileType::class, null, $options);
    }

    private function createView(array $options = []): FormView
    {
        return $this->createForm($options)
            ->createView();
    }
}
