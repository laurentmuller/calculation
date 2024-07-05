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

use App\Form\Extension\AbstractFileTypeExtension;
use App\Form\Extension\FileTypeExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

#[CoversClass(AbstractFileTypeExtension::class)]
#[CoversClass(FileTypeExtension::class)]
class FileTypeExtensionTest extends TypeTestCase
{
    public function testFormViewWithMaxFiles(): void
    {
        $options = ['maxfiles' => 10];
        $view = $this->factory->create(FileType::class, null, $options)
            ->createView();
        $attributes = $view->vars['attr'];
        self::assertArrayHasKey('maxfiles', $attributes);
        self::assertSame(10, $attributes['maxfiles']);
    }

    public function testFormViewWithMaxFilesOne(): void
    {
        $options = ['maxfiles' => 1];
        $view = $this->factory->create(FileType::class, null, $options)
            ->createView();
        $attributes = $view->vars['attr'];
        self::assertArrayNotHasKey('maxfiles', $attributes);
    }

    public function testFormViewWithMaxSize(): void
    {
        $options = ['maxsize' => '10'];
        $view = $this->factory->create(FileType::class, null, $options)
            ->createView();
        $attributes = $view->vars['attr'];
        self::assertArrayHasKey('maxsize', $attributes);
        self::assertSame(10, $attributes['maxsize']);
    }

    public function testFormViewWithMaxSizeInKb(): void
    {
        $options = ['maxsize' => '1k'];
        $view = $this->factory->create(FileType::class, null, $options)
            ->createView();
        $attributes = $view->vars['attr'];
        self::assertArrayHasKey('maxsize', $attributes);
        self::assertSame(1_000, $attributes['maxsize']);
    }

    public function testFormViewWithMaxSizeInvalid(): void
    {
        self::expectException(InvalidOptionsException::class);
        $options = ['maxsize' => 'fake'];
        $this->factory->create(FileType::class, null, $options)
            ->createView();
    }

    public function testFormViewWithMaxSizeTotal(): void
    {
        $options = ['maxsizetotal' => '10'];
        $view = $this->factory->create(FileType::class, null, $options)
            ->createView();
        $attributes = $view->vars['attr'];
        self::assertArrayHasKey('maxsizetotal', $attributes);
        self::assertSame(10, $attributes['maxsizetotal']);
    }

    public function testFormViewWithMaxSizeZero(): void
    {
        $options = ['maxsize' => 0];
        $view = $this->factory->create(FileType::class, null, $options)
            ->createView();
        $attributes = $view->vars['attr'];
        self::assertArrayHasKey('maxsize', $attributes);
        self::assertNull($attributes['maxfiles']);
    }

    public function testFormWithMaxFile(): void
    {
        $form = $this->factory->create(FileType::class, null, ['maxfiles' => 1]);
        $options = $form->getConfig()
            ->getOptions();
        self::assertArrayHasKey('maxfiles', $options);
    }

    public function testFormWithMaxSize(): void
    {
        $form = $this->factory->create(FileType::class, null, ['maxsize' => 1]);
        $options = $form->getConfig()
            ->getOptions();
        self::assertArrayHasKey('maxsize', $options);
    }

    public function testFormWithMaxSizeTotal(): void
    {
        $form = $this->factory->create(FileType::class, null, ['maxsizetotal' => 1]);
        $options = $form->getConfig()
            ->getOptions();
        self::assertArrayHasKey('maxsizetotal', $options);
    }

    public function testFormWithNoOption(): void
    {
        $form = $this->factory->create(FileType::class);
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

    protected function getTypeExtensions(): array
    {
        /** @psalm-var array $extensions */
        $extensions = parent::getTypeExtensions();
        $extensions[] = new FileTypeExtension();

        return $extensions;
    }
}
