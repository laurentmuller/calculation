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

namespace App\Tests\Form;

use App\Enums\MessagePosition;
use App\Form\DataTransformer\AddressTransformer;
use App\Form\Extension\FileTypeExtension;
use App\Form\Extension\TextTypeExtension;
use App\Form\Extension\UrlTypeExtension;
use App\Form\Extension\VichImageTypeExtension;
use App\Form\FormHelper;
use App\Form\Type\CurrentPasswordType;
use App\Form\Type\PlainType;
use App\Form\Type\RepeatPasswordType;
use App\Pdf\Html\HtmlAttribute;
use App\Tests\Form\User\PasswordHasherExtensionTrait;
use App\Tests\Form\User\VichImageTypeTrait;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Vich\UploaderBundle\Form\Type\VichImageType;

class FormHelperTest extends TypeTestCase
{
    use PasswordHasherExtensionTrait;
    use PreloadedExtensionsTrait {
        getExtensions as getExtensionsFromTrait;
    }
    use TranslatorMockTrait;
    use ValidatorExtensionTrait;
    use VichImageTypeTrait;

    public function testAutoCompleteEmpty(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->autocomplete('')
            ->addTextType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameAttribute($form, 'autocomplete', null);
    }

    public function testBuilder(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->getBuilder();
        self::assertInstanceOf(FormBuilderInterface::class, $actual);
    }

    public function testCheckboxType(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addCheckboxType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameType($form, CheckboxType::class);
    }

    public function testChoiceType(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addChoiceType(['key' => 'value'])
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameType($form, ChoiceType::class);
    }

    public function testCollectionType(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addCollectionType(TextType::class)
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameType($form, CollectionType::class);
    }

    public function testCollectionTypeException(): void
    {
        self::expectException(UnexpectedValueException::class);
        $helper = $this->getFormHelper();
        $helper->field('name')
            ->addCollectionType(self::class);
    }

    public function testColorType(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addColorType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameType($form, ColorType::class);
    }

    public function testCurrentPasswordType(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addCurrentPasswordType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameType($form, CurrentPasswordType::class);
    }

    public function testDateType(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addDateType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameType($form, DateType::class);
    }

    public function testDisabled(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->disabled()
            ->addTextType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameOption($form, 'disabled', true);
    }

    public function testDomain(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->domain('domain.test')
            ->addTextType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameOption($form, 'translation_domain', 'domain.test');
    }

    public function testEmailType(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addEmailType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameType($form, EmailType::class);
    }

    public function testEnumTypeNoReadable(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addEnumType(HtmlAttribute::class)
            ->createForm();
        self::assertCount(1, $form);
    }

    public function testEnumTypeSortableAndReadable(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addEnumType(MessagePosition::class)
            ->createForm();
        self::assertCount(1, $form);
    }

    public function testFaxType(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addFaxType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameType($form, TelType::class);
    }

    public function testFieldPrefix(): void
    {
        $helper = $this->getFormHelper('prefix.');
        $form = $helper->field('name')
            ->addTextType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameOption($form, 'label', 'prefix.name');
    }

    public function testFileTypeWithExtension(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addFileType('png')
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameType($form, FileType::class);
    }

    public function testFileTypeWithoutExtension(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addFileType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameType($form, FileType::class);
    }

    public function testHelpHtml(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->helpHtml()
            ->addTextType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameOption($form, 'help_html', true);
    }

    public function testHiddenType(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addHiddenType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameType($form, HiddenType::class);
    }

    public function testLabelEmpty(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->label('')
            ->addTextType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameAttribute($form, 'label', null);
    }

    public function testListenerPreSetData(): void
    {
        $listener = fn (): null => null;
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->listenerPreSetData($listener)
            ->addTextType()
            ->createForm();
        self::assertCount(1, $form);
        $listeners = $helper->getBuilder()->getEventDispatcher()
            ->getListeners(FormEvents::PRE_SET_DATA);
        self::assertContains($listener, $listeners);
    }

    public function testListenerPreSubmit(): void
    {
        $listener = fn (): null => null;
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->listenerPreSubmit($listener)
            ->addTextType()
            ->createForm();
        self::assertCount(1, $form);
        $listeners = $helper->getBuilder()->getEventDispatcher()
            ->getListeners(FormEvents::PRE_SUBMIT);
        self::assertContains($listener, $listeners);
    }

    public function testMaxLength(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->maxLength(20)
            ->addTextType()
            ->createForm();
        self::assertCount(1, $form);
        $attributes = $this->getAttrArray($form);
        self::assertArrayHasKey('maxLength', $attributes);
        self::assertSame(20, $attributes['maxLength']);
    }

    public function testMinLength(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->minLength(20)
            ->addTextType()
            ->createForm();
        self::assertCount(1, $form);
        $attributes = $this->getAttrArray($form);
        self::assertArrayHasKey('minLength', $attributes);
        self::assertSame(20, $attributes['minLength']);
    }

    public function testNumberType(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addNumberType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameType($form, NumberType::class);
    }

    public function testPercentHidden(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->percent(false)
            ->addPercentType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameOption($form, 'symbol', false);
    }

    public function testPercentType(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addPercentType(0, 100)
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameType($form, PercentType::class);
    }

    public function testPercentVisible(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->percent(true)
            ->addPercentType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameOption($form, 'symbol', '%');
    }

    public function testPlainType(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addPlainType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameType($form, PlainType::class);
    }

    public function testPriority(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->priority(100)
            ->addTextType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameOption($form, 'priority', 100);
    }

    public function testReadonly(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->readonly()
            ->addTextType()
            ->createForm();
        self::assertCount(1, $form);
        $attributes = $this->getAttrArray($form);
        self::assertArrayHasKey('readonly', $attributes);
        self::assertTrue($attributes['readonly']);
    }

    public function testRepeatPasswordTypeWithOptions(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addRepeatPasswordType('password.option', 'confirm.option')
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameType($form, RepeatPasswordType::class);
    }

    public function testRepeatPasswordTypeWithoutOptions(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addRepeatPasswordType()
            ->createForm();
        self::assertCount(1, $form);
    }

    public function testRowClass(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->rowClass('row-class')
            ->addTextType()
            ->createForm();
        self::assertCount(1, $form);
        $attributes = $this->getRowAttrArray($form);
        self::assertArrayHasKey('class', $attributes);
        /** @psalm-var string $actual */
        $actual = $attributes['class'];
        self::assertStringContainsString('row-class', $actual);
    }

    public function testSimulateAndConfirmType(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addSimulateAndConfirmType($this->createMockTranslator(), false)
            ->createForm();
        self::assertCount(2, $form);
    }

    public function testTelType(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addTelType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameType($form, TelType::class);
    }

    public function testTextareaType(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addTextareaType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameType($form, TextareaType::class);
    }

    public function testTextType(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addTextType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameType($form, TextType::class);
    }

    public function testTrueFalseType(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addTrueFalseType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameType($form, ChoiceType::class);
    }

    public function testUpdateRowAttributes(): void
    {
        $attributes = [
            'present' => true,
            'missing' => null,
        ];
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->updateRowAttributes($attributes)
            ->addTextType()
            ->createForm();
        self::assertCount(1, $form);
    }

    public function testUrlType(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addUrlType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameType($form, UrlType::class);
    }

    public function testUserNameType(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addUserNameType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameType($form, TextType::class);
    }

    public function testVichImageType(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->addVichImageType()
            ->createForm();
        self::assertCount(1, $form);
        self::assertSameType($form, VichImageType::class);
    }

    public function testWidgetClassEmpty(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->widgetClass('')
            ->addTextType()
            ->createForm();
        $attributes = $this->getAttrArray($form);
        self::assertArrayNotHasKey('class', $attributes);
    }

    public function testWithModelTransformer(): void
    {
        $helper = $this->getFormHelper();
        $form = $helper->field('name')
            ->modelTransformer(new AddressTransformer())
            ->addTextType()
            ->createForm();
        self::assertCount(1, $form);
    }

    protected static function assertSameAttribute(FormInterface $form, string $name, mixed $expected): void
    {
        self::assertTrue($form->has('name'));
        $field = $form->get('name');
        /** @psalm-var mixed $actual */
        $actual = $field->getConfig()
            ->getAttribute($name);
        self::assertSame($expected, $actual);
    }

    protected static function assertSameOption(FormInterface $form, string $name, mixed $expected): void
    {
        self::assertTrue($form->has('name'));
        $field = $form->get('name');
        /** @psalm-var mixed $actual */
        $actual = $field->getConfig()
            ->getOption($name);
        self::assertSame($expected, $actual);
    }

    /**
     * @psalm-param class-string $expected
     */
    protected static function assertSameType(FormInterface $form, string $expected): void
    {
        self::assertTrue($form->has('name'));
        $field = $form->get('name');
        $actual = $field->getConfig()
            ->getType()
            ->getInnerType();
        self::assertSame($expected, $actual::class);
    }

    /**
     * @throws Exception|\ReflectionException
     */
    protected function getExtensions(): array
    {
        $extensions = $this->getExtensionsFromTrait();
        $extensions[] = $this->getPasswordHasherExtension();

        return $extensions;
    }

    /**
     * @throws Exception
     */
    protected function getPreloadedExtensions(): array
    {
        return [
            new PlainType($this->createMockTranslator()),
            $this->createVichImageType(),
        ];
    }

    protected function getTypeExtensions(): array
    {
        return [
            new FileTypeExtension(),
            new TextTypeExtension(),
            new UrlTypeExtension(),
            new VichImageTypeExtension(),
        ];
    }

    private function getAttrArray(FormInterface $form): array
    {
        $field = $form->get('name');
        $attributes = $field->getConfig()
            ->getOption('attr');
        self::assertIsArray($attributes);

        return $attributes;
    }

    private function getFormHelper(?string $labelPrefix = null): FormHelper
    {
        $builder = $this->factory->createBuilder();

        return new FormHelper($builder, $labelPrefix);
    }

    private function getRowAttrArray(FormInterface $form): array
    {
        $field = $form->get('name');
        $attributes = $field->getConfig()
            ->getOption('row_attr');
        self::assertIsArray($attributes);

        return $attributes;
    }
}
