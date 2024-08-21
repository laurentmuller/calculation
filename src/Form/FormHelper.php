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

namespace App\Form;

use App\Form\Type\CurrentPasswordType;
use App\Form\Type\PlainType;
use App\Form\Type\RepeatPasswordType;
use App\Interfaces\EnumSortableInterface;
use App\Interfaces\UserInterface;
use App\Utils\FormatUtils;
use App\Utils\StringUtils;
use Elao\Enum\Bridge\Symfony\Form\Type\EnumType as ElaoEnumType;
use Elao\Enum\ReadableEnumInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
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
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;

/**
 * Helper class to add types to a form builder.
 */
class FormHelper
{
    /**
     * The attributes.
     *
     * @var array<string, mixed>
     */
    private array $attributes = [];

    /**
     * The field identifier.
     */
    private string $field = '';

    /**
     * The help attributes.
     *
     * @var array<string, mixed>
     */
    private array $helpAttributes = [];

    /**
     * The label attributes.
     *
     * @var array<string, mixed>
     */
    private array $labelAttributes = [];

    /**
     * The labels prefix.
     */
    private readonly ?string $labelPrefix;

    /**
     * The data transformer.
     *
     * @psalm-var ?DataTransformerInterface<mixed, mixed> $modelTransformer
     */
    private ?DataTransformerInterface $modelTransformer = null;

    /**
     * The options.
     *
     * @var array<string, mixed>
     */
    private array $options = [];

    /**
     * The row attributes.
     *
     * @var array<string, mixed>
     */
    private array $rowAttributes = ['class' => 'mb-3 form-group'];

    /**
     * @param FormBuilderInterface $builder     the parent builder
     * @param ?string              $labelPrefix the label prefix.
     *                                          If the prefix is not null, the label is added automatically
     *                                          when the field property is set.
     */
    public function __construct(private readonly FormBuilderInterface $builder, ?string $labelPrefix = null)
    {
        $this->labelPrefix = StringUtils::isString($labelPrefix) ? $labelPrefix : null;
    }

    /**
     * Adds a new field to this builder and reset all values to default.
     *
     * The field must have a unique name. Otherwise, the existing field is overwritten.
     *
     * @template T of FormTypeInterface
     *
     * @param class-string<T> $type the child type to add
     */
    public function add(string $type): self
    {
        $field = $this->field;
        $this->builder->add($field, $type, $this->getOptions());
        if ($this->modelTransformer instanceof DataTransformerInterface) {
            $this->builder->get($field)->addModelTransformer($this->modelTransformer);
        }

        return $this->reset();
    }

    /**
     * Add a checkbox type to the builder and reset all values to default.
     *
     * @param bool $notRequired <code>true</code> if not required; <code>false</code> if required
     * @param bool $switch      <code>true</code> to use the checkbox switch style, <code>false</code> to use
     *                          the default style
     */
    public function addCheckboxType(bool $notRequired = true, bool $switch = true): self
    {
        if ($switch) {
            $this->labelClass('checkbox-switch');
        }
        if ($notRequired) {
            $this->notRequired();
        }

        return $this->add(CheckboxType::class);
    }

    /**
     * Adds a choice type to the builder and reset all values to default.
     *
     * @param array $choices an array, where the array key is the item's label, and the array value is the item's value
     */
    public function addChoiceType(array $choices): self
    {
        return $this->updateOption('choices', $choices)
            ->add(ChoiceType::class);
    }

    /**
     * Add a collection type to the builder with the given entry type and reset all values to default.
     *
     * @param string               $entryType the entry type class must be a subclass of the FormTypeInterface class
     * @param array<string, mixed> $options   the default options to override
     *
     * @throws UnexpectedValueException if the entry type is not an instance of FormTypeInterface class
     */
    public function addCollectionType(string $entryType, array $options = []): self
    {
        if (!\is_a($entryType, FormTypeInterface::class, true)) {
            throw new UnexpectedValueException($entryType, FormTypeInterface::class);
        }

        $options = \array_merge([
            'label' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'entry_type' => $entryType,
            'entry_options' => ['label' => false],
        ], $options);

        return $this->updateOptions($options)
            ->add(CollectionType::class);
    }

    /**
     * Add a color type to the builder and reset all values to default.
     *
     * @param bool $colorPicker true to wrap widget to a color-picker
     */
    public function addColorType(bool $colorPicker = true): self
    {
        if ($colorPicker) {
            $this->widgetClass('color-picker');
        }

        return $this->add(ColorType::class);
    }

    /**
     * Add a current password type to the builder and reset all values to default.
     */
    public function addCurrentPasswordType(): self
    {
        return $this->add(CurrentPasswordType::class);
    }

    /**
     * Add a date type to the builder and reset all values to default.
     */
    public function addDateType(): self
    {
        return $this->updateOption('widget', 'single_text')
            ->add(DateType::class);
    }

    /**
     * Add an email type to the builder and reset all values to default.
     */
    public function addEmailType(): self
    {
        return $this->updateAttribute('inputmode', 'email')
            ->updateOption('prepend_icon', 'fa-fw fa-solid fa-at')
            ->updateOption('prepend_class', 'input-group-email')
            ->add(EmailType::class);
    }

    /**
     * Add an enum type to the builder and reset all values to default.
     *
     * @param string $class the enumeration class
     *
     * @psalm-template T of \UnitEnum
     *
     * @psalm-param class-string<T> $class
     */
    public function addEnumType(string $class): self
    {
        $this->updateOption('class', $class);
        if (\is_a($class, EnumSortableInterface::class, true)) {
            $this->updateOption('choices', $class::sorted());
        }
        if (\is_a($class, ReadableEnumInterface::class, true)) {
            return $this->add(ElaoEnumType::class);
        }

        return $this->add(EnumType::class);
    }

    /**
     * Add a fax (telephone) type to the builder and reset all values to default.
     */
    public function addFaxType(?string $pattern = null): self
    {
        return $this->updateAttribute('pattern', $pattern)
            ->updateOption('prepend_icon', 'fa-fw fa-solid fa-fax')
            ->updateOption('prepend_class', 'input-group-fax')
            ->add(TelType::class);
    }

    /**
     * Add a file type to the builder and reset all values to default.
     *
     * @param string $extension the allowed file extension
     */
    public function addFileType(string $extension = ''): self
    {
        if ('' !== $extension) {
            $this->updateAttribute('accept', $this->getMimeTypes($extension))
                ->constraints(new File(extensions: $extension));
        }

        return $this->add(FileType::class);
    }

    /**
     * Add a hidden type to the builder and reset all values to default.
     */
    public function addHiddenType(): self
    {
        return $this->add(HiddenType::class);
    }

    /**
     * Add a number type to the builder and reset all values to default.
     *
     * @param int $scale the number of decimals to set
     */
    public function addNumberType(int $scale = 2): self
    {
        $input_mode = $scale > 0 ? 'decimal' : 'numeric';

        return $this->widgetClass('text-end')
            ->updateAttribute('inputmode', $input_mode)
            ->updateAttribute('scale', $scale)
            ->updateOption('html5', true)
            ->add(NumberType::class);
    }

    /**
     * Add a percent type to the builder and reset all values to default.
     *
     * @param ?int  $min  the minimum value allowed (inclusive) or null if none
     * @param ?int  $max  the maximum value allowed (inclusive) or null if none
     * @param float $step the step increment or a negative value if none
     */
    public function addPercentType(?int $min = null, ?int $max = null, float $step = 1.0): self
    {
        $this->widgetClass('text-end')
            ->updateAttribute('inputmode', 'decimal')
            ->updateOption('html5', true)
            ->autocomplete('off');

        if (null !== $min) {
            $this->updateAttribute('min', $min);
        }
        if (null !== $max) {
            $this->updateAttribute('max', $max);
        }
        if ($step > 0) {
            $this->updateAttribute('step', $step);
        }

        return $this->add(PercentType::class);
    }

    /**
     * Add a plain type to the builder and reset all values to default.
     *
     * This type just renders the field as a span tag. This is useful for
     * forms where certain field needs to be shown but not editable.
     *
     * @param bool $expanded true to render the plain type within the label
     */
    public function addPlainType(bool $expanded = true): self
    {
        if ($expanded) {
            $this->updateOption('expanded', true);
        }

        return $this->notRequired()->add(PlainType::class);
    }

    /**
     * Add a repeat password type to the builder and reset all values to default.
     *
     * @param string $passwordLabel the label used for the password
     * @param string $confirmLabel  the label used for the confirmation password
     */
    public function addRepeatPasswordType(
        string $passwordLabel = RepeatPasswordType::PASSWORD_LABEL,
        string $confirmLabel = RepeatPasswordType::CONFIRM_LABEL
    ): self {
        if (RepeatPasswordType::PASSWORD_LABEL !== $passwordLabel) {
            $first_options = \array_replace_recursive(
                RepeatPasswordType::getPasswordOptions(),
                ['label' => $passwordLabel]
            );
            $this->updateOption('first_options', $first_options);
        }
        if (RepeatPasswordType::CONFIRM_LABEL !== $confirmLabel) {
            $second_options = \array_replace_recursive(
                RepeatPasswordType::getConfirmOptions(),
                ['label' => $confirmLabel]
            );
            $this->updateOption('second_options', $second_options);
        }

        return $this->add(RepeatPasswordType::class);
    }

    /**
     * Add checkbox inputs to simulate and confirm an operation.
     */
    public function addSimulateAndConfirmType(TranslatorInterface $translator, bool $disabled): self
    {
        $this->field('simulate')
            ->label('simulate.label')
            ->help('simulate.help')
            ->helpClass('ms-4')
            ->addCheckboxType();

        $this->field('confirm')
            ->label('simulate.confirm')
            ->updateAttributes([
                'data-error' => $translator->trans('simulate.error'),
                'disabled' => $disabled ? 'disabled' : null,
            ])
            ->notMapped()
            ->addCheckboxType(false);

        return $this;
    }

    /**
     * Add a telephone type to the builder and reset all values to default.
     */
    public function addTelType(?string $pattern = null): self
    {
        return $this->updateOption('prepend_icon', 'fa-fw fa-solid fa-phone')
            ->updateOption('prepend_class', 'input-group-phone')
            ->updateAttribute('inputmode', 'tel')
            ->updateAttribute('pattern', $pattern)
            ->add(TelType::class);
    }

    /**
     * Add a text area type to the builder and reset all values to default.
     */
    public function addTextareaType(int $rows = 2, string $widgetClass = 'resizable'): self
    {
        return $this->updateAttribute('rows', $rows)
            ->widgetClass($widgetClass)
            ->add(TextareaType::class);
    }

    /**
     * Add a text type to the builder and reset all values to default.
     */
    public function addTextType(): self
    {
        return $this->add(TextType::class);
    }

    /**
     * Add a True/False choice type to the builder and reset all values to default.
     *
     * @param string           $trueText    the translatable text to use for the "True" value
     * @param string           $falseText   the translatable text to use for the "False" value
     * @param string|bool|null $translation determines if the choice values should be translated and in which
     *                                      translation domain
     */
    public function addTrueFalseType(
        string $trueText = 'common.value_true',
        string $falseText = 'common.value_false',
        string|bool|null $translation = true
    ): self {
        return $this->updateOption('choices', [$trueText => true, $falseText => false])
            ->updateOption('choice_translation_domain', $translation)
            ->add(ChoiceType::class);
    }

    /**
     * Add an Url type to the builder and reset all values to default.
     *
     * @param string $protocol If a value is submitted, that doesn't begin with some protocol,
     *                         (http://, ftp://, etc.), this protocol will be prepended to
     *                         the string when the data is submitted to the form.
     */
    public function addUrlType(string $protocol = 'https'): self
    {
        return $this->updateOption('default_protocol', $protocol)
            ->updateOption('prepend_icon', 'fa-fw fa-solid fa-globe')
            ->updateOption('prepend_class', 'input-group-url')
            ->updateAttribute('inputmode', 'url')
            ->add(UrlType::class);
    }

    /**
     * Add a username type.
     */
    public function addUserNameType(string|bool $autocomplete = 'username'): self
    {
        return $this->updateOption('prepend_icon', 'fa-fw fa-regular fa-user')
            ->autocomplete($autocomplete)
            ->updateAttributes([
                'minLength' => UserInterface::MIN_USERNAME_LENGTH,
                'maxLength' => UserInterface::MAX_USERNAME_LENGTH,
            ])
            ->addTextType();
    }

    /**
     * Adds a Vich image type and reset all values to default.
     */
    public function addVichImageType(): self
    {
        // see https://github.com/kartik-v/bootstrap-fileinput
        $this->notRequired()
            ->updateRowAttribute('class', 'mb-0')
            ->updateOptions([
                'translation_domain' => 'messages',
                'download_uri' => false,
            ])->updateAttributes([
                'accept' => 'image/gif,image/jpeg,image/png,image/bmp',
                'title' => '',
            ]);

        // labels
        if (!isset($this->options['delete_label'])) {
            $this->updateOption('delete_label', false);
        }

        return $this->add(VichImageType::class);
    }

    /**
     * Sets the autocomplete attribute.
     *
     * For Google Chrome, if You want to disable the auto-complete, set a random string as the attribute like 'nope'.
     *
     * @param bool|string $autocomplete the autocomplete ('on'/'off') or false to remove
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/autocomplete
     */
    public function autocomplete(bool|string $autocomplete): self
    {
        if ('' === $autocomplete) {
            $autocomplete = null;
        }

        return $this->updateAttribute('autocomplete', $autocomplete);
    }

    /**
     * Sets constraints option.
     */
    public function constraints(Constraint ...$constrains): self
    {
        if (1 === \count($constrains)) {
            $constrains = \reset($constrains);
        }

        return $this->updateOption('constraints', $constrains);
    }

    /**
     * Creates the form within the underlying form builder.
     *
     * @return FormInterface<mixed> the form
     *
     * @see FormBuilderInterface::getForm()
     */
    public function createForm(): FormInterface
    {
        return $this->builder->getForm();
    }

    /**
     * Sets the disabled property to true.
     */
    public function disabled(): self
    {
        return $this->updateOption('disabled', true);
    }

    /**
     * Sets the translation domain.
     *
     * Use <code>null</code> to reuse the translation domain of the parent form. Use <code>false</code>
     * to disable translations.
     *
     * @param string|false|null $domain the translation domain to set
     */
    public function domain(string|false|null $domain): self
    {
        return $this->updateOption('translation_domain', $domain);
    }

    /**
     * Sets the field name property.
     *
     * If the label prefix is defined, the label is added automatically.
     *
     * @param string $field the field name
     */
    public function field(string $field): self
    {
        $this->field = $field;

        // add label if applicable
        if (null !== $this->labelPrefix && !\in_array('label_format', $this->options, true)) {
            return $this->label($this->labelPrefix . $field);
        }

        return $this;
    }

    /**
     * Gets the underlying form builder.
     */
    public function getBuilder(): FormBuilderInterface
    {
        return $this->builder;
    }

    /**
     * Sets the help property.
     *
     * @param ?string $help the help identifier to translate
     */
    public function help(?string $help): self
    {
        $help = StringUtils::isString($help) ? $help : null;

        return $this->updateOption('help', $help);
    }

    /**
     * Add a class name to the help class attributes.
     *
     * @param string $classNames one or more space-separated classes to be added to the help class attribute
     */
    public function helpClass(string $classNames): self
    {
        return $this->addClasses($this->helpAttributes, $classNames);
    }

    /**
     * Sets the help HTML property.
     *
     * Set this option to true to not escape them, which is useful when the help contains HTML elements.
     *
     * @param bool $html <code>true</code> if required, <code>false</code> otherwise
     */
    public function helpHtml(bool $html = true): self
    {
        return $this->updateOption('help_html', $html);
    }

    /**
     * Sets the label property.
     *
     * @param TranslatableInterface|string|false $label the translatable, the label identifier to translate or false
     *                                                  to hide
     */
    public function label(string|\Stringable|TranslatableInterface|false $label): self
    {
        if ('' === $label) {
            $label = null;
        }

        return $this->updateOption('label', $label);
    }

    /**
     * Add a class name to the label class attributes.
     *
     * @param string $classNames one or more space-separated classes to be added to the label class attribute
     */
    public function labelClass(string $classNames): self
    {
        return $this->addClasses($this->labelAttributes, $classNames);
    }

    /**
     * Adds a pre-set-data event listener to this form builder.
     *
     * @param callable(PreSetDataEvent): void $listener the event listener to add
     * @param int                             $priority The priority of the listener. Listeners with the higher
     *                                                  priority is called before listeners with a lower priority.
     */
    public function listenerPreSetData(callable $listener, int $priority = 0): self
    {
        $this->builder->addEventListener(FormEvents::PRE_SET_DATA, $listener, $priority);

        return $this;
    }

    /**
     * Adds a pre-submit event listener to this form builder.
     *
     * @param callable(PreSubmitEvent): void $listener $listener the event listener to add
     * @param int                            $priority The priority of the listener. Listeners with the higher
     *                                                 priority is called before listeners with a lower priority.
     */
    public function listenerPreSubmit(callable $listener, int $priority = 0): self
    {
        $this->builder->addEventListener(FormEvents::PRE_SUBMIT, $listener, $priority);

        return $this;
    }

    /**
     * Sets the maximum length.
     *
     * @param int $maxLength the maximum length or 0 to remove the attribute
     */
    public function maxLength(int $maxLength): self
    {
        return $this->updateAttribute('maxLength', $maxLength > 0 ? $maxLength : null);
    }

    /**
     * Sets the minimum length.
     *
     * @param int $minLength the minimum length or 0 to remove the attribute
     */
    public function minLength(int $minLength): self
    {
        return $this->updateAttribute('minLength', $minLength > 0 ? $minLength : null);
    }

    /**
     * Sets the model transformer.
     *
     * @psalm-template TValue
     * @psalm-template TTransformedValue
     *
     * @psalm-param DataTransformerInterface<TValue, TTransformedValue>|null $modelTransformer
     */
    public function modelTransformer(?DataTransformerInterface $modelTransformer): static
    {
        $this->modelTransformer = $modelTransformer;

        return $this;
    }

    /**
     * Sets the mapped property to false.
     *
     * Used if you wish the field to be ignored when reading or writing to the object.
     */
    public function notMapped(): self
    {
        return $this->updateOption('mapped', false);
    }

    /**
     * Sets the required property to false.
     */
    public function notRequired(): self
    {
        return $this->required(false);
    }

    /**
     * Sets the percent symbol visibility.
     *
     * @param bool $visible true to display the percent symbol; false to hide
     */
    public function percent(bool $visible): self
    {
        return $this->updateOption('symbol', $visible ? FormatUtils::PERCENT_SYMBOL : false);
    }

    /**
     * Sets the priority.
     *
     * @param int $priority the priority to set.
     *                      Fields with higher priorities are rendered first, and fields with the same priority
     *                      are rendered in their original order.
     */
    public function priority(int $priority): self
    {
        return $this->updateOption('priority', $priority);
    }

    /**
     * Sets the read-only property to true.
     */
    public function readonly(): self
    {
        return $this->updateAttribute('readonly', true);
    }

    /**
     * Sets the required property.
     *
     * @param bool $required <code>true</code> if required, <code>false</code> otherwise
     */
    public function required(bool $required): self
    {
        return $this->updateOption('required', $required);
    }

    /**
     * Reset all options and attributes to the default values.
     */
    public function reset(): self
    {
        $this->options = [];
        $this->attributes = [];
        $this->rowAttributes = ['class' => 'mb-3 form-group'];
        $this->helpAttributes = [];
        $this->labelAttributes = [];
        $this->modelTransformer = null;

        return $this;
    }

    /**
     * Add a class name to the row class attributes.
     *
     * @param string $classNames one or more space-separated classes to be added to the row class attribute
     */
    public function rowClass(string $classNames): self
    {
        return $this->addClasses($this->rowAttributes, $classNames);
    }

    /**
     * Updates an attribute.
     */
    public function updateAttribute(string $name, mixed $value): self
    {
        if (null === $value) {
            unset($this->attributes[$name]);
        } else {
            $this->attributes[$name] = $value;
        }

        return $this;
    }

    /**
     * Update attributes.
     *
     * @param array<string, mixed> $attributes the attribute's names and values
     */
    public function updateAttributes(array $attributes): self
    {
        /** @psalm-var mixed $value */
        foreach ($attributes as $name => $value) {
            $this->updateAttribute($name, $value);
        }

        return $this;
    }

    /**
     * Updates an option.
     */
    public function updateOption(string $name, mixed $value): self
    {
        if (null === $value) {
            unset($this->options[$name]);
        } else {
            $this->options[$name] = $value;
        }

        return $this;
    }

    /**
     * Update options.
     *
     * @param array<string, mixed> $options the option's name and value
     */
    public function updateOptions(array $options): self
    {
        /** @psalm-var mixed $value */
        foreach ($options as $name => $value) {
            $this->updateOption($name, $value);
        }

        return $this;
    }

    /**
     * Updates a row attribute.
     */
    public function updateRowAttribute(string $name, mixed $value): self
    {
        if (null === $value) {
            unset($this->rowAttributes[$name]);
        } else {
            $this->rowAttributes[$name] = $value;
        }

        return $this;
    }

    /**
     * Updates row's attributes.
     *
     * @param array<string, mixed> $attributes the attribute's names and values
     */
    public function updateRowAttributes(array $attributes): self
    {
        /** @psalm-var mixed $value */
        foreach ($attributes as $name => $value) {
            $this->updateRowAttribute($name, $value);
        }

        return $this;
    }

    /**
     * Add a class name to the widget class attribute.
     *
     * @param string $classNames one or more space-separated classes to be added to the widget class attribute
     */
    public function widgetClass(string $classNames): self
    {
        return $this->addClasses($this->attributes, $classNames);
    }

    /**
     * Add one or more classes. Do nothing if the given class names are empty.
     *
     * @psalm-param array<string, mixed> $array
     */
    private function addClasses(array &$array, string $classNames): self
    {
        if ('' === \trim($classNames)) {
            return $this;
        }

        $existing = (string) ($array['class'] ?? '');
        $oldValues = \array_filter(\explode(' ', $existing));
        $newValues = \array_filter(\explode(' ', $classNames));
        $className = \implode(' ', \array_unique([...$oldValues, ...$newValues]));

        if (StringUtils::isString($className)) {
            $array['class'] = $className;
        } else {
            unset($array['class']);
        }

        return $this;
    }

    private function getMimeTypes(string $extension): string
    {
        $default = MimeTypes::getDefault();
        $types = $default->getMimeTypes($extension);

        return \implode(',', $types);
    }

    /**
     * @return array<string, mixed>
     */
    private function getOptions(): array
    {
        $attributes = \array_filter([
            'attr' => $this->attributes,
            'row_attr' => $this->rowAttributes,
            'help_attr' => $this->helpAttributes,
            'label_attr' => $this->labelAttributes,
        ]);
        foreach ($attributes as $name => $value) {
            $this->options[$name] = $value;
        }

        return $this->options;
    }
}
