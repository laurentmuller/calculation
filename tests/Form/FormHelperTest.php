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
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Vich\UploaderBundle\Form\Type\VichImageType;

final class FormHelperTest extends TypeTestCase
{
    use PasswordHasherExtensionTrait;
    use PreloadedExtensionsTrait {
        getExtensions as getExtensionsFromTrait;
    }
    use TranslatorMockTrait;
    use ValidatorExtensionTrait;
    use VichImageTypeTrait;

    private const FIELD = 'name';

    public function testAutoCompleteEmpty(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->autocomplete('')
            ->addTextType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameAttribute($actual, 'autocomplete', null);
    }

    public function testCheckboxTypeBoth(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addCheckboxType(inline: true)
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, CheckboxType::class);
        $this->assertSameLabelAttribute($actual, 'class', 'checkbox-switch checkbox-inline');
    }

    public function testCheckboxTypeDefault(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addCheckboxType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, CheckboxType::class);
        $this->assertSameLabelAttribute($actual, 'class', 'checkbox-switch');
    }

    public function testCheckboxTypeInline(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addCheckboxType(switch: false, inline: true)
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, CheckboxType::class);
        $this->assertSameLabelAttribute($actual, 'class', 'checkbox-inline');
    }

    public function testCheckboxTypeNone(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addCheckboxType(switch: false)
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, CheckboxType::class);
        $this->assertSameLabelAttribute($actual, 'class', null);
    }

    public function testChoiceType(): void
    {
        $choices = ['key' => 'value'];
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addChoiceType($choices)
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, ChoiceType::class);
        $this->assertSameOption($actual, 'choices', $choices);
    }

    public function testCollectionType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addCollectionType(TextType::class)
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, CollectionType::class);
    }

    public function testCollectionTypeException(): void
    {
        self::expectException(UnexpectedValueException::class);
        $helper = $this->getFormHelper();
        $helper->field(self::FIELD)
            ->addCollectionType(self::class);
    }

    public function testColorType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addColorType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, ColorType::class);
        $this->assertSameAttribute($actual, 'class', 'color-picker');
    }

    public function testColorTypeNoPicker(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addColorType(false)
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, ColorType::class);
        $this->assertSameAttribute($actual, 'class', null);
    }

    public function testCurrentPasswordType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addCurrentPasswordType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, CurrentPasswordType::class);
        $this->assertSameOption($actual, 'mapped', false);
    }

    public function testDatePointType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addDatePointType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, DateType::class);
        $this->assertSameOption($actual, 'widget', 'single_text');
    }

    public function testDisabled(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->disabled()
            ->addTextType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameOption($actual, 'disabled', true);
    }

    public function testDomain(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->domain('domain.test')
            ->addTextType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameOption($actual, 'translation_domain', 'domain.test');
    }

    public function testEmailType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addEmailType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, EmailType::class);
        $this->assertSameAttribute($actual, 'inputmode', 'email');
    }

    public function testEnumTypeNoReadable(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addEnumType(HtmlAttribute::class)
            ->createForm();
        self::assertCount(1, $actual);
    }

    public function testEnumTypeSortableAndReadable(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addEnumType(MessagePosition::class)
            ->createForm();
        self::assertCount(1, $actual);
    }

    public function testFieldPrefix(): void
    {
        $helper = $this->getFormHelper('prefix.');
        $actual = $helper->field(self::FIELD)
            ->addTextType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameOption($actual, 'label', 'prefix.name');
    }

    public function testFileTypeWithExtension(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addFileType('png')
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, FileType::class);
    }

    public function testFileTypeWithoutExtension(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addFileType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, FileType::class);
    }

    public function testHelpHtml(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->helpHtml()
            ->addTextType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameOption($actual, 'help_html', true);
    }

    public function testHiddenType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addHiddenType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, HiddenType::class);
    }

    public function testLabelEmpty(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->label('')
            ->addTextType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameAttribute($actual, 'label', null);
    }

    public function testListenerPreSetData(): void
    {
        $listener = static fn (): null => null;
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->listenerPreSetData($listener)
            ->addTextType()
            ->createForm();
        self::assertCount(1, $actual);
        $listeners = $helper->getBuilder()->getEventDispatcher()
            ->getListeners(FormEvents::PRE_SET_DATA);
        self::assertContains($listener, $listeners);
    }

    public function testListenerPreSubmit(): void
    {
        $listener = static fn (): null => null;
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->listenerPreSubmit($listener)
            ->addTextType()
            ->createForm();
        self::assertCount(1, $actual);
        $listeners = $helper->getBuilder()->getEventDispatcher()
            ->getListeners(FormEvents::PRE_SUBMIT);
        self::assertContains($listener, $listeners);
    }

    public function testMaxLength(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->maxLength(20)
            ->addTextType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameAttribute($actual, 'maxLength', 20);
    }

    public function testMinLength(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->minLength(20)
            ->addTextType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameAttribute($actual, 'minLength', 20);
    }

    public function testNumberType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addNumberType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, NumberType::class);
        $this->assertSameOption($actual, 'html5', true);
        $this->assertSameAttribute($actual, 'scale', 2);
        $this->assertSameAttribute($actual, 'inputmode', 'decimal');
        $this->assertSameAttribute($actual, 'class', 'text-end');
    }

    public function testNumberTypeNoDecimal(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addNumberType(0)
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, NumberType::class);
        $this->assertSameOption($actual, 'html5', true);
        $this->assertSameAttribute($actual, 'scale', 0);
        $this->assertSameAttribute($actual, 'inputmode', 'numeric');
        $this->assertSameAttribute($actual, 'class', 'text-end');
    }

    public function testPasswordType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addPasswordType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, PasswordType::class);
        $this->assertSameAttribute($actual, 'autocomplete', 'current-password');
    }

    public function testPercentHidden(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->percent(false)
            ->addPercentType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameOption($actual, 'symbol', false);
    }

    public function testPercentType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addPercentType(0, 100)
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, PercentType::class);
    }

    public function testPercentVisible(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->percent(true)
            ->addPercentType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameOption($actual, 'symbol', '%');
    }

    public function testPlainType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addPlainType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, PlainType::class);
    }

    public function testPriority(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->priority(100)
            ->addTextType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameOption($actual, 'priority', 100);
    }

    public function testReadonly(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->readonly()
            ->addTextType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameAttribute($actual, 'readonly', true);
    }

    public function testRepeatPasswordTypeWithOptions(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addRepeatPasswordType('password.option', 'confirm.option')
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, RepeatPasswordType::class);
    }

    public function testRepeatPasswordTypeWithoutOptions(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addRepeatPasswordType()
            ->createForm();
        self::assertCount(1, $actual);
    }

    public function testRowClass(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->rowClass('row-class')
            ->addTextType()
            ->createForm();
        $this->assertSameRowAttribute($actual, 'class', 'mb-3 form-group row-class');
    }

    public function testSimulateAndConfirmType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addSimulateAndConfirmType($this->createMockTranslator(), false)
            ->createForm();
        self::assertCount(2, $actual);
    }

    public function testTelType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addTelType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, TelType::class);
    }

    public function testTextareaType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addTextareaType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, TextareaType::class);
        $this->assertSameAttribute($actual, 'rows', 2);
        $this->assertSameAttribute($actual, 'class', 'resizable');
    }

    public function testTextType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addTextType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, TextType::class);
        $this->assertSameRowAttribute($actual, 'class', 'mb-3 form-group');
    }

    public function testTrueFalseType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addTrueFalseType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, ChoiceType::class);
    }

    public function testUpdateRowAttributes(): void
    {
        $attributes = [
            'present' => true,
            'missing' => null,
        ];
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->updateRowAttributes($attributes)
            ->addTextType()
            ->createForm();
        self::assertCount(1, $actual);
    }

    public function testUrlType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addUrlType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, UrlType::class);
    }

    public function testUserNameType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addUserNameType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, TextType::class);
    }

    public function testVichImageType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addVichImageType()
            ->createForm();
        self::assertCount(1, $actual);
        $this->assertSameType($actual, VichImageType::class);
    }

    public function testWidgetClassDuplicate(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->widgetClass('form-check form-check')
            ->addTextType()
            ->createForm();
        $this->assertSameAttribute($actual, 'class', 'form-check');
    }

    public function testWidgetClassEmpty(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->widgetClass('')
            ->addTextType()
            ->createForm();
        $this->assertSameAttribute($actual, 'class', null);
    }

    public function testWithModelTransformer(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->modelTransformer(new AddressTransformer())
            ->addTextType()
            ->createForm();
        self::assertCount(1, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    #[\Override]
    protected function getExtensions(): array
    {
        $extensions = $this->getExtensionsFromTrait();
        $extensions[] = $this->getPasswordHasherExtension();

        return $extensions;
    }

    #[\Override]
    protected function getPreloadedExtensions(): array
    {
        return [
            new PlainType($this->createMockTranslator()),
            $this->createVichImageType(),
        ];
    }

    /**
     * @return array{
     *     FileTypeExtension,
     *     TextTypeExtension,
     *     UrlTypeExtension,
     *     VichImageTypeExtension
     * }
     */
    #[\Override]
    protected function getTypeExtensions(): array
    {
        return [
            new FileTypeExtension(),
            new TextTypeExtension(),
            new UrlTypeExtension(),
            new VichImageTypeExtension(),
        ];
    }

    /**
     * @phpstan-param FormInterface<mixed> $form
     */
    private function assertSameAttribute(FormInterface $form, string $name, mixed $expected): void
    {
        self::assertTrue($form->has(self::FIELD));
        $field = $form->get(self::FIELD);
        $this->validateAttribute($field->getConfig(), 'attr', $name, $expected);
    }

    /**
     * @phpstan-param FormInterface<mixed> $form
     */
    private function assertSameLabelAttribute(FormInterface $form, string $name, mixed $expected): void
    {
        self::assertTrue($form->has(self::FIELD));
        $field = $form->get(self::FIELD);
        $this->validateAttribute($field->getConfig(), 'label_attr', $name, $expected);
    }

    /**
     * @phpstan-param FormInterface<mixed> $form
     */
    private function assertSameOption(FormInterface $form, string $name, mixed $expected): void
    {
        self::assertTrue($form->has(self::FIELD));
        $field = $form->get(self::FIELD);
        $config = $field->getConfig();
        if (null === $expected) {
            self::assertFalse($config->hasOption($name));
        } else {
            self::assertTrue($config->hasOption($name));
            self::assertSame($expected, $config->getOption($name));
        }
    }

    /**
     * @phpstan-param FormInterface<mixed> $form
     */
    private function assertSameRowAttribute(FormInterface $form, string $name, mixed $expected): void
    {
        self::assertTrue($form->has(self::FIELD));
        $field = $form->get(self::FIELD);
        $this->validateAttribute($field->getConfig(), 'row_attr', $name, $expected);
    }

    /**
     * @phpstan-param FormInterface<mixed> $form
     * @phpstan-param class-string $expected
     */
    private function assertSameType(FormInterface $form, string $expected): void
    {
        self::assertTrue($form->has(self::FIELD));
        $field = $form->get(self::FIELD);
        $innerType = $field->getConfig()
            ->getType()
            ->getInnerType();
        $actual = $innerType::class;
        self::assertSame($expected, $actual);
    }

    private function getFormHelper(?string $labelPrefix = null): FormHelper
    {
        $builder = $this->factory->createBuilder();

        return new FormHelper($builder, $labelPrefix);
    }

    /**
     * @phpstan-param FormConfigInterface<mixed> $config
     */
    private function validateAttribute(FormConfigInterface $config, string $option, string $name, mixed $expected): void
    {
        self::assertTrue($config->hasOption($option));
        $actual = $config->getOption($option);
        self::assertIsArray($actual);
        if (null === $expected) {
            self::assertArrayNotHasKey($name, $actual);
        } else {
            self::assertArrayHasKey($name, $actual);
            self::assertSame($expected, $actual[$name]);
        }
    }
}
