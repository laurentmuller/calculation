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
use App\Form\Extension\InputGroupTypeExtension;
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
use Elao\Enum\Bridge\Symfony\Form\Type\EnumType as ElaoEnumType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
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
        $this->validateForm(
            form: $actual,
            attributes: ['autocomplete' => null]
        );
    }

    public function testCheckboxTypeBoth(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addCheckboxType(inline: true)
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: CheckboxType::class,
            options: ['label_attr' => ['class' => 'checkbox-switch checkbox-inline']]
        );
    }

    public function testCheckboxTypeDefault(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addCheckboxType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: CheckboxType::class,
            options: ['label_attr' => ['class' => 'checkbox-switch']]
        );
    }

    public function testCheckboxTypeInline(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addCheckboxType(switch: false, inline: true)
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: CheckboxType::class,
            options: ['label_attr' => ['class' => 'checkbox-inline']]
        );
    }

    public function testCheckboxTypeNone(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addCheckboxType(switch: false)
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: CheckboxType::class,
            options: ['label_attr' => []]
        );
    }

    public function testChoiceType(): void
    {
        $choices = ['key' => 'value'];
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addChoiceType($choices)
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: ChoiceType::class,
            options: ['choices' => $choices]
        );
    }

    public function testCollectionType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addCollectionType(TextType::class)
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: CollectionType::class,
            options: [
                'label' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_type' => TextType::class,
            ]
        );
    }

    public function testColorType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addColorType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: ColorType::class,
            attributes: ['class' => 'color-picker']
        );
    }

    public function testColorTypeNoPicker(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addColorType(false)
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: ColorType::class,
            attributes: ['class' => null]
        );
    }

    public function testCurrentPasswordType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addCurrentPasswordType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: CurrentPasswordType::class,
            options: ['mapped' => false]
        );
    }

    public function testDatePointType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addDatePointType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: DateType::class,
            options: ['widget' => 'single_text']
        );
    }

    public function testDisabled(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->disabled()
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            options: ['disabled' => true]
        );
    }

    public function testDomain(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->domain('domain.test')
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            options: ['translation_domain' => 'domain.test']
        );
    }

    public function testEmailType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addEmailType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: EmailType::class,
            attributes: ['inputmode' => 'email']
        );
    }

    public function testEnumTypeNoReadable(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addEnumType(HtmlAttribute::class)
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: EnumType::class,
        );
    }

    public function testEnumTypeSortableAndReadable(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addEnumType(MessagePosition::class)
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: ElaoEnumType::class,
        );
    }

    public function testFieldPrefix(): void
    {
        $helper = $this->getFormHelper('prefix.');
        $actual = $helper->field(self::FIELD)
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            options: ['label' => 'prefix.name']
        );
    }

    public function testFileTypeWithExtension(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addFileType('png')
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: FileType::class,
        );
    }

    public function testFileTypeWithoutExtension(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addFileType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: FileType::class,
        );
    }

    public function testHelpHtml(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->helpHtml()
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            options: ['help_html' => true]
        );
    }

    public function testHiddenType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addHiddenType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: HiddenType::class,
        );
    }

    public function testLabelEmpty(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->label('')
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            attributes: ['label' => null]
        );
    }

    public function testListenerPreSetData(): void
    {
        $listener = static fn (): null => null;
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->listenerPreSetData($listener)
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $actual,
        );
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
        $this->validateForm(
            form: $actual,
        );
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
        $this->validateForm(
            form: $actual,
            attributes: ['maxLength' => 20]
        );
    }

    public function testMinLength(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->minLength(20)
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            attributes: ['minLength' => 20]
        );
    }

    public function testMoneyType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addMoneyType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: MoneyType::class,
            options: [
                'html5' => true,
                'scale' => 2,
                'currency' => 'CHF',
                'input' => 'float',
            ],
            attributes: ['class' => 'text-end']
        );
    }

    public function testMoneyTypeNoCurrency(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addMoneyType('')
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: MoneyType::class,
            options: [
                'html5' => true,
                'scale' => 2,
                'currency' => '',
                'input' => 'float',
            ],
            attributes: ['class' => 'text-end']
        );
    }

    public function testNumberType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addNumberType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: NumberType::class,
            options: ['html5' => true],
            attributes: [
                'scale' => 2,
                'inputmode' => 'decimal',
                'class' => 'text-end',
            ]
        );
    }

    public function testNumberTypeNoDecimal(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addNumberType(0)
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: NumberType::class,
            options: ['html5' => true],
            attributes: [
                'scale' => 0,
                'inputmode' => 'numeric',
                'class' => 'text-end',
            ]
        );
    }

    public function testPasswordType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addPasswordType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: PasswordType::class,
            attributes: ['autocomplete' => 'current-password']
        );
    }

    public function testPercentHidden(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->percent(false)
            ->addPercentType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: PercentType::class,
            options: ['symbol' => false]
        );
    }

    public function testPercentType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addPercentType(0, 100, 2.0)
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: PercentType::class,
            options: ['symbol' => '%'],
            attributes: [
                'min' => 0,
                'max' => 100,
                'step' => 2.0,
            ]
        );
    }

    public function testPercentVisible(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->percent(true)
            ->addPercentType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: PercentType::class,
            options: ['symbol' => '%']
        );
    }

    public function testPlainType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addPlainType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: PlainType::class
        );
    }

    public function testPriority(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->priority(100)
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            options: ['priority' => 100]
        );
    }

    public function testReadonly(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->readonly()
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            attributes: ['readonly' => true]
        );
    }

    public function testRepeatPasswordTypeWithOptions(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addRepeatPasswordType('password.option', 'confirm.option')
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: RepeatPasswordType::class,
            options: [
                'first_options' => [
                    'label' => 'password.option',
                    'hash_property_path' => 'password',
                    'attr' => [
                        'minlength' => 6,
                        'maxlength' => 255,
                        'class' => 'password-strength',
                        'autocomplete' => 'new-password',
                    ],
                ],
                'second_options' => [
                    'label' => 'confirm.option',
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'maxlength' => 255,
                    ],
                ],
            ]
        );
    }

    public function testRepeatPasswordTypeWithoutOptions(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addRepeatPasswordType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: RepeatPasswordType::class,
            options: [
                'first_options' => [
                    'label' => 'user.password.label',
                    'hash_property_path' => 'password',
                    'attr' => [
                        'minlength' => 6,
                        'maxlength' => 255,
                        'class' => 'password-strength',
                        'autocomplete' => 'new-password',
                    ],
                ],
                'second_options' => [
                    'label' => 'user.password.confirmation',
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'maxlength' => 255,
                    ],
                ],
            ]
        );
    }

    public function testRowClass(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->rowClass('row-class')
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            options: [
                'row_attr' => ['class' => 'mb-3 form-group row-class'],
            ]
        );
    }

    public function testSimulateAndConfirmType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addSimulateAndConfirmType($this->createMockTranslator(), false)
            ->createForm();

        self::assertTrue($actual->has('simulate'));
        $this->validateForm(
            form: $actual,
            class: CheckboxType::class,
            count: 2,
            options: [
                'label' => 'simulate.label',
                'help' => 'simulate.help',
                'help_attr' => [
                    'class' => 'ms-4',
                ],
            ],
            fieldName: 'simulate',
        );

        self::assertTrue($actual->has('confirm'));
        $this->validateForm(
            form: $actual,
            class: CheckboxType::class,
            count: 2,
            options: [
                'label' => 'simulate.confirm',
                'mapped' => false,
            ],
            attributes: [
                'data-error' => 'simulate.error',
                'disabled' => null,
            ],
            fieldName: 'confirm',
        );
    }

    public function testTelType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addTelType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: TelType::class,
            attributes: ['inputmode' => 'tel']
        );
    }

    public function testTextareaType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addTextareaType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: TextareaType::class,
            attributes: [
                'rows' => 2,
                'class' => 'resizable',
            ]
        );
    }

    public function testTextType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            options: [
                'row_attr' => ['class' => 'mb-3 form-group'],
            ]
        );
    }

    public function testTrueFalseType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addTrueFalseType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: ChoiceType::class,
            options: [
                'choices' => [
                    'common.value_true' => true,
                    'common.value_false' => false,
                ],
            ]
        );
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
        $this->validateForm(
            form: $actual,
            options: [
                'row_attr' => [
                    'class' => 'mb-3 form-group',
                    'present' => true,
                ],
            ]
        );
    }

    public function testUrlType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addUrlType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: UrlType::class,
            options: ['default_protocol' => 'https'],
            attributes: ['inputmode' => 'url']
        );
    }

    public function testUserNameType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addUserNameType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            attributes: [
                'autocomplete' => 'username',
                'minLength' => 2,
                'maxLength' => 180,
            ]
        );
    }

    public function testVichImageType(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->addVichImageType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            class: VichImageType::class,
            options: [
                'translation_domain' => 'messages',
                'download_uri' => false,
            ],
            attributes: ['accept' => 'image/gif,image/jpeg,image/png,image/bmp']
        );
    }

    public function testWidgetClassDuplicate(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->widgetClass('form-check form-check')
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            attributes: ['class' => 'form-check']
        );
    }

    public function testWidgetClassEmpty(): void
    {
        $helper = $this->getFormHelper();
        $actual = $helper->field(self::FIELD)
            ->widgetClass('')
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $actual,
            attributes: ['class' => null]
        );
    }

    public function testWithModelTransformer(): void
    {
        $helper = $this->getFormHelper();
        $transformer = new AddressTransformer();
        $form = $helper->field(self::FIELD)
            ->modelTransformer($transformer)
            ->addTextType()
            ->createForm();
        $field = $form->get(self::FIELD);
        $configuration = $field->getConfig();
        $transformers = $configuration->getModelTransformers();
        self::assertContains($transformer, $transformers);
    }

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
     *     InputGroupTypeExtension,
     *     UrlTypeExtension,
     *     VichImageTypeExtension
     * }
     */
    #[\Override]
    protected function getTypeExtensions(): array
    {
        return [
            new FileTypeExtension(),
            new InputGroupTypeExtension(),
            new UrlTypeExtension(),
            new VichImageTypeExtension(),
        ];
    }

    /**
     * @phpstan-param FormConfigInterface<mixed> $configuration
     */
    private function assertSameAttribute(FormConfigInterface $configuration, string $name, mixed $expected): void
    {
        $actual = $configuration->getOption('attr');
        self::assertIsArray($actual);
        if (null === $expected) {
            self::assertArrayNotHasKey($name, $actual);
        } else {
            self::assertArrayHasKey($name, $actual);
            self::assertSame($expected, $actual[$name]);
        }
    }

    /**
     * @phpstan-param FormInterface<mixed> $form
     */
    private function assertSameCount(FormInterface $form, int $expected = 1): void
    {
        self::assertCount($expected, $form);
    }

    /**
     * @phpstan-param FormConfigInterface<mixed> $configuration
     */
    private function assertSameOption(FormConfigInterface $configuration, string $name, mixed $expected): void
    {
        if (null === $expected) {
            self::assertFalse($configuration->hasOption($name));
        } else {
            self::assertTrue($configuration->hasOption($name));
            self::assertSame($expected, $configuration->getOption($name));
        }
    }

    /**
     * @phpstan-param FormConfigInterface<mixed> $configuration
     */
    private function assertSameType(FormConfigInterface $configuration, string $expected): void
    {
        $actual = $configuration->getType()
            ->getInnerType()::class;
        self::assertSame($expected, $actual);
    }

    private function getFormHelper(?string $labelPrefix = null): FormHelper
    {
        $builder = $this->factory->createBuilder();

        return new FormHelper($builder, $labelPrefix);
    }

    /**
     * @phpstan-param FormInterface<mixed> $form
     * @phpstan-param class-string $class
     */
    private function validateForm(
        FormInterface $form,
        string $class = TextType::class,
        int $count = 1,
        array $options = [],
        array $attributes = [],
        string $fieldName = self::FIELD
    ): void {
        $field = $form->get($fieldName);
        $configuration = $field->getConfig();
        $this->assertSameCount($form, $count);
        $this->assertSameType($configuration, $class);
        foreach ($options as $name => $value) {
            $this->assertSameOption($configuration, $name, $value);
        }
        foreach ($attributes as $name => $value) {
            $this->assertSameAttribute($configuration, $name, $value);
        }
    }
}
