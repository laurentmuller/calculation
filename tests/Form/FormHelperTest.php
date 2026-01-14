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
use App\Form\User\UserImageType;
use App\Pdf\Html\HtmlAttribute;
use App\Tests\Fixture\FixtureStringable;
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
        $form = $this->createFormHelper()
            ->autocomplete('')
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $form,
            attributes: ['autocomplete' => null]
        );
    }

    public function testCheckboxTypeBoth(): void
    {
        $form = $this->createFormHelper()
            ->addCheckboxType(inline: true)
            ->createForm();
        $this->validateForm(
            form: $form,
            class: CheckboxType::class,
            options: ['label_attr' => ['class' => 'checkbox-switch checkbox-inline']]
        );
    }

    public function testCheckboxTypeDefault(): void
    {
        $form = $this->createFormHelper()
            ->addCheckboxType()
            ->createForm();
        $this->validateForm(
            form: $form,
            class: CheckboxType::class,
            options: ['label_attr' => ['class' => 'checkbox-switch']]
        );
    }

    public function testCheckboxTypeInline(): void
    {
        $form = $this->createFormHelper()
            ->addCheckboxType(switch: false, inline: true)
            ->createForm();
        $this->validateForm(
            form: $form,
            class: CheckboxType::class,
            options: ['label_attr' => ['class' => 'checkbox-inline']]
        );
    }

    public function testCheckboxTypeNone(): void
    {
        $form = $this->createFormHelper()
            ->addCheckboxType(switch: false)
            ->createForm();
        $this->validateForm(
            form: $form,
            class: CheckboxType::class,
            options: ['label_attr' => []]
        );
    }

    public function testChoiceType(): void
    {
        $choices = ['key' => 'value'];
        $form = $this->createFormHelper()
            ->addChoiceType($choices)
            ->createForm();
        $this->validateForm(
            form: $form,
            class: ChoiceType::class,
            options: ['choices' => $choices]
        );
    }

    public function testCollectionType(): void
    {
        $form = $this->createFormHelper()
            ->addCollectionType(TextType::class)
            ->createForm();
        $this->validateForm(
            form: $form,
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
        $form = $this->createFormHelper()
            ->addColorType()
            ->createForm();
        $this->validateForm(
            form: $form,
            class: ColorType::class,
            attributes: ['class' => 'color-picker']
        );
    }

    public function testColorTypeNoPicker(): void
    {
        $form = $this->createFormHelper()
            ->addColorType(false)
            ->createForm();
        $this->validateForm(
            form: $form,
            class: ColorType::class,
            attributes: ['class' => null]
        );
    }

    public function testCurrentPasswordType(): void
    {
        $form = $this->createFormHelper()
            ->addCurrentPasswordType()
            ->createForm();
        $this->validateForm(
            form: $form,
            class: CurrentPasswordType::class,
            options: ['mapped' => false]
        );
    }

    public function testDatePointType(): void
    {
        $form = $this->createFormHelper()
            ->addDatePointType()
            ->createForm();
        $this->validateForm(
            form: $form,
            class: DateType::class,
            options: ['widget' => 'single_text']
        );
    }

    public function testDisabled(): void
    {
        $form = $this->createFormHelper()
            ->disabled()
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $form,
            options: ['disabled' => true]
        );
    }

    public function testDomain(): void
    {
        $form = $this->createFormHelper()
            ->domain('domain.test')
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $form,
            options: ['translation_domain' => 'domain.test']
        );
    }

    public function testDuplicateClass(): void
    {
        $form = $this->createFormHelper()
            ->labelClass('text-start text-end')
            ->labelClass('text-start')
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $form,
            options: ['label_attr' => ['class' => 'text-start text-end']],
        );
    }

    public function testEmailType(): void
    {
        $form = $this->createFormHelper()
            ->addEmailType()
            ->createForm();
        $this->validateForm(
            form: $form,
            class: EmailType::class,
            attributes: ['inputmode' => 'email']
        );
    }

    public function testEnumTypeNoReadable(): void
    {
        $form = $this->createFormHelper()
            ->addEnumType(HtmlAttribute::class)
            ->createForm();
        $this->validateForm(
            form: $form,
            class: EnumType::class,
        );
    }

    public function testEnumTypeSortableAndReadable(): void
    {
        $form = $this->createFormHelper()
            ->addEnumType(MessagePosition::class)
            ->createForm();
        $this->validateForm(
            form: $form,
            class: ElaoEnumType::class,
        );
    }

    public function testFieldPrefix(): void
    {
        $form = $this->createFormHelper('prefix.')
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $form,
            options: ['label' => 'prefix.name']
        );
    }

    public function testFileTypeWithExtension(): void
    {
        $form = $this->createFormHelper()
            ->addFileType('png')
            ->createForm();
        $this->validateForm(
            form: $form,
            class: FileType::class,
        );
    }

    public function testFileTypeWithoutExtension(): void
    {
        $form = $this->createFormHelper()
            ->addFileType()
            ->createForm();
        $this->validateForm(
            form: $form,
            class: FileType::class,
        );
    }

    public function testHelpStringable(): void
    {
        $form = $this->createFormHelper()
            ->help(new FixtureStringable())
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $form,
            attributes: ['help' => null]
        );
    }

    public function testHiddenType(): void
    {
        $form = $this->createFormHelper()
            ->addHiddenType()
            ->createForm();
        $this->validateForm(
            form: $form,
            class: HiddenType::class,
        );
    }

    public function testLabelEmpty(): void
    {
        $form = $this->createFormHelper()
            ->label('')
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $form,
            attributes: ['label' => null]
        );
    }

    public function testLabelStringable(): void
    {
        $form = $this->createFormHelper()
            ->label(new FixtureStringable())
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $form,
            attributes: ['label' => null]
        );
    }

    public function testListenerPreSetData(): void
    {
        $listener = static fn (): null => null;
        $helper = $this->createFormHelper();
        $form = $helper->listenerPreSetData($listener)
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $form,
        );
        $listeners = $helper->getBuilder()->getEventDispatcher()
            ->getListeners(FormEvents::PRE_SET_DATA);
        self::assertContains($listener, $listeners);
    }

    public function testListenerPreSubmit(): void
    {
        $listener = static fn (): null => null;
        $helper = $this->createFormHelper();
        $form = $helper->listenerPreSubmit($listener)
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $form,
        );
        $listeners = $helper->getBuilder()->getEventDispatcher()
            ->getListeners(FormEvents::PRE_SUBMIT);
        self::assertContains($listener, $listeners);
    }

    public function testMaxLength(): void
    {
        $form = $this->createFormHelper()
            ->maxLength(20)
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $form,
            attributes: ['maxLength' => 20]
        );
    }

    public function testMinLength(): void
    {
        $form = $this->createFormHelper()
            ->minLength(20)
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $form,
            attributes: ['minLength' => 20]
        );
    }

    public function testMoneyType(): void
    {
        $form = $this->createFormHelper()
            ->addMoneyType()
            ->createForm();
        $this->validateForm(
            form: $form,
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
        $form = $this->createFormHelper()
            ->addMoneyType('')
            ->createForm();
        $this->validateForm(
            form: $form,
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
        $form = $this->createFormHelper()
            ->addNumberType()
            ->createForm();
        $this->validateForm(
            form: $form,
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
        $form = $this->createFormHelper()
            ->addNumberType(0)
            ->createForm();
        $this->validateForm(
            form: $form,
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
        $form = $this->createFormHelper()
            ->addPasswordType()
            ->createForm();
        $this->validateForm(
            form: $form,
            class: PasswordType::class,
            attributes: ['autocomplete' => 'current-password']
        );
    }

    public function testPercentHidden(): void
    {
        $form = $this->createFormHelper()
            ->percent(false)
            ->addPercentType()
            ->createForm();
        $this->validateForm(
            form: $form,
            class: PercentType::class,
            options: ['symbol' => false]
        );
    }

    public function testPercentType(): void
    {
        $form = $this->createFormHelper()
            ->addPercentType(0, 100, 2.0)
            ->createForm();
        $this->validateForm(
            form: $form,
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
        $form = $this->createFormHelper()
            ->percent(true)
            ->addPercentType()
            ->createForm();
        $this->validateForm(
            form: $form,
            class: PercentType::class,
            options: ['symbol' => '%']
        );
    }

    public function testPlainType(): void
    {
        $form = $this->createFormHelper()
            ->addPlainType()
            ->createForm();
        $this->validateForm(
            form: $form,
            class: PlainType::class
        );
    }

    public function testReadonly(): void
    {
        $form = $this->createFormHelper()
            ->readonly()
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $form,
            attributes: ['readonly' => true]
        );
    }

    public function testRepeatPasswordTypeWithOptions(): void
    {
        $form = $this->createFormHelper()
            ->addRepeatPasswordType('password.option', 'confirm.option')
            ->createForm();
        $this->validateForm(
            form: $form,
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
        $form = $this->createFormHelper()
            ->addRepeatPasswordType()
            ->createForm();
        $this->validateForm(
            form: $form,
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
        $form = $this->createFormHelper()
            ->rowClass('row-class')
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $form,
            options: [
                'row_attr' => ['class' => 'mb-3 form-group row-class'],
            ]
        );
    }

    public function testSimulateAndConfirmType(): void
    {
        $form = $this->createFormHelper()
            ->addSimulateAndConfirmType($this->createMockTranslator(), false)
            ->createForm();

        self::assertTrue($form->has('simulate'));
        $this->validateForm(
            form: $form,
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

        self::assertTrue($form->has('confirm'));
        $this->validateForm(
            form: $form,
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
        $form = $this->createFormHelper()
            ->addTelType()
            ->createForm();
        $this->validateForm(
            form: $form,
            class: TelType::class,
            attributes: ['inputmode' => 'tel']
        );
    }

    public function testTextareaType(): void
    {
        $form = $this->createFormHelper()
            ->addTextareaType()
            ->createForm();
        $this->validateForm(
            form: $form,
            class: TextareaType::class,
            attributes: [
                'rows' => 2,
                'class' => 'resizable',
            ]
        );
    }

    public function testTextType(): void
    {
        $form = $this->createFormHelper()
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $form,
            options: [
                'row_attr' => ['class' => 'mb-3 form-group'],
            ]
        );
    }

    public function testTrueFalseType(): void
    {
        $form = $this->createFormHelper()
            ->addTrueFalseType()
            ->createForm();
        $this->validateForm(
            form: $form,
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
        $form = $this->createFormHelper()
            ->updateRowAttributes($attributes)
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $form,
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
        $form = $this->createFormHelper()
            ->addUrlType()
            ->createForm();
        $this->validateForm(
            form: $form,
            class: UrlType::class,
            options: ['default_protocol' => 'https'],
            attributes: ['inputmode' => 'url']
        );
    }

    public function testUserImageType(): void
    {
        $form = $this->createFormHelper()
            ->addUserImageType()
            ->createForm();
        $this->validateForm(
            form: $form,
            class: UserImageType::class,
            options: [
                'maxsize' => '10mi',
                'required' => false,
                'download_uri' => false,
                'translation_domain' => 'messages',
                'delete_label' => 'user.edit.delete_image',
            ],
            attributes: ['accept' => 'image/gif,image/jpeg,image/png,image/bmp']
        );
    }

    public function testUserNameType(): void
    {
        $form = $this->createFormHelper()
            ->addUserNameType()
            ->createForm();
        $this->validateForm(
            form: $form,
            attributes: [
                'autocomplete' => 'username',
                'minLength' => 2,
                'maxLength' => 180,
            ]
        );
    }

    public function testWidgetClassDuplicate(): void
    {
        $form = $this->createFormHelper()
            ->widgetClass('form-check form-check')
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $form,
            attributes: ['class' => 'form-check']
        );
    }

    public function testWidgetClassEmpty(): void
    {
        $form = $this->createFormHelper()
            ->widgetClass('')
            ->addTextType()
            ->createForm();
        $this->validateForm(
            form: $form,
            attributes: ['class' => null]
        );
    }

    public function testWithModelTransformer(): void
    {
        $transformer = new AddressTransformer();
        $form = $this->createFormHelper()
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
     * @param FormConfigInterface<mixed> $configuration
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
     * @param FormInterface<mixed> $form
     */
    private function assertSameCount(FormInterface $form, int $expected = 1): void
    {
        self::assertCount($expected, $form);
    }

    /**
     * @param FormConfigInterface<mixed> $configuration
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
     * @param FormConfigInterface<mixed> $configuration
     * @param class-string               $expected
     */
    private function assertSameType(FormConfigInterface $configuration, string $expected): void
    {
        $actual = $configuration->getType()
            ->getInnerType()::class;
        self::assertSame($expected, $actual);
    }

    private function createFormHelper(?string $labelPrefix = null): FormHelper
    {
        $builder = $this->factory->createBuilder();
        $helper = new FormHelper($builder, $labelPrefix);

        return $helper->field(self::FIELD);
    }

    /**
     * @param FormInterface<mixed> $form
     * @param class-string         $class
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
