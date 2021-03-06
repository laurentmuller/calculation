<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Form;

use App\Form\CalculationState\CalculationStateListType;
use App\Form\Category\CategoryListType;
use App\Form\Type\PlainType;
use App\Form\Type\RepeatPasswordType;
use App\Form\Type\YesNoType;
use App\Form\User\UserListType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
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
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Vich\UploaderBundle\Form\Type\VichImageType;

/**
 * Helper class to add types to a form builder.
 *
 * @author Laurent Muller
 */
class FormHelper
{
    /**
     * The attributes.
     */
    private array $attributes = [];

    /**
     * The parent builder.
     */
    private FormBuilderInterface $builder;

    /**
     * The field identifier.
     */
    private ?string $field = null;

    /**
     * The help attributes.
     */
    private array $helpAttributes = [];

    /**
     * The label attributes.
     */
    private array $labelAttributes = [];

    /**
     * The labels prefix.
     */
    private ?string $labelPrefix = null;

    /**
     * The options.
     */
    private array $options = [];

    /**
     * The row attributes.
     */
    private array $rowAttributes = [];

    /**
     * Constructor.
     *
     * @param FormBuilderInterface $builder     the parent builder
     * @param string               $labelPrefix the label prefix. If the prefix is not null,
     *                                          the label is automatically added when the field property is
     *                                          set.
     */
    public function __construct(FormBuilderInterface $builder, ?string $labelPrefix = null)
    {
        $this->builder = $builder;
        $this->labelPrefix = empty($labelPrefix) ? null : $labelPrefix;
    }

    /**
     * Adds a new field to this builder and reset all values to default.
     *
     * @param string $type the child type to add
     */
    public function add(string $type): self
    {
        // merge options and attributes
        if (!empty($this->attributes)) {
            $this->options['attr'] = $this->attributes;
        }
        if (!empty($this->rowAttributes)) {
            $this->options['row_attr'] = $this->rowAttributes;
        }
        if (!empty($this->helpAttributes)) {
            $this->options['help_attr'] = $this->helpAttributes;
        }
        if (!empty($this->labelAttributes)) {
            $this->options['label_attr'] = $this->labelAttributes;
        }

        // add
        $this->builder->add($this->field, $type, $this->options);

        return $this->reset();
    }

    /**
     * Add a birthday type to the builder and reset all values to default.
     */
    public function addBirthdayType(): self
    {
        return $this->updateOption('widget', 'single_text')
            ->add(BirthdayType::class);
    }

    /**
     * Add a calculation state list type to the builder and reset all values to default.
     *
     * This type display a drop-down list of CalculationState entities.
     */
    public function addCalculationStateListType(): self
    {
        return $this->add(CalculationStateListType::class);
    }

    /**
     * Add a category list type to the builder and reset all values to default.
     *
     * This type display a drop-down list of Category entities.
     */
    public function addCategoryListType(): self
    {
        return $this->add(CategoryListType::class);
    }

    /**
     * Add a checkbox type to the builder and reset all values to default.
     *
     * @param bool $switchStyle true to render the checkbox with the toggle switch style
     */
    public function addCheckboxType(bool $switchStyle = true): self
    {
        if ($switchStyle) {
            $this->labelClass('switch-custom');
        }

        return $this->add(CheckboxType::class);
    }

    /**
     * Adds a choice type to the builder and reset all values to default.
     *
     * @param array $choices an array, where the array key is the item's label and the array value is the item's value
     */
    public function addChoiceType(array $choices): self
    {
        return $this->updateOption('choices', $choices)
            ->add(ChoiceType::class);
    }

    /**
     * Add a collection type to the builder with the given entry type and reset all values to default.
     *
     * @param string $entryType    the entry type class
     * @param bool   $allow_add    true to allow user to add a new entry
     * @param bool   $allow_delete true to allow user to delete an entry
     */
    public function addCollectionType(string $entryType, bool $allow_add = true, bool $allow_delete = true): self
    {
        return $this->updateOption('entry_type', $entryType)
            ->updateOption('by_reference', false)
            ->updateOption('allow_add', $allow_add)
            ->updateOption('allow_delete', $allow_delete)
            ->updateOption('label', false)
            ->updateOption('entry_options', ['label' => false])
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
        return $this->add(EmailType::class);
    }

    /**
     * Adds an event listener to an event on this form builder.
     *
     * @param string   $eventName the event name
     * @param callable $listener  the event listener
     * @param int      $priority  The priority of the listener. Listeners
     *                            with a higher priority are called before
     *                            listeners with a lower priority.
     */
    public function addEventListener(string $eventName, callable $listener, int $priority = 0): self
    {
        $this->builder->addEventListener($eventName, $listener, $priority);

        return $this;
    }

    /**
     * Add a file type to the builder and reset all values to default.
     */
    public function addFileType(): self
    {
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
        return $this->widgetClass('text-right')
            ->updateOption('html5', true)
            ->updateAttribute('scale', $scale)
            ->add(NumberType::class);
    }

    /**
     * Add a password type to the builder and reset all values to default.
     */
    public function addPassordType(): self
    {
        return $this->add(PasswordType::class);
    }

    /**
     * Add a percent type to the builder and reset all values to default.
     *
     * @param int   $min  the minimum value allowed (inclusive) or <code>PHP_INT_MIN</code> if none
     * @param int   $max  the maximum value allowed (inclusive) or <code>PHP_INT_MAX</code> if none
     * @param float $step the step increment or -1 if none
     */
    public function addPercentType(int $min = \PHP_INT_MIN, int $max = \PHP_INT_MAX, float $step = 1.0): self
    {
        $this->widgetClass('text-right')
            ->updateOption('html5', true)
            ->autocomplete('off');

        if (\PHP_INT_MIN !== $min) {
            $this->updateAttribute('min', $min);
        }
        if (\PHP_INT_MAX !== $max) {
            $this->updateAttribute('max', $max);
        }
        if (-1 !== $step) {
            $this->updateAttribute('step', $step);
        }

        // needed for Symfony v5.1
        $this->updateOption('rounding_mode', \NumberFormatter::ROUND_HALFUP);

        return $this->add(PercentType::class);
    }

    /**
     * Add a plain type to the builder and reset all values to default.
     * This type just renders the field as a span tag. This is useful for
     * forms where certain field need to be shown but not editable.
     *
     *  @param bool $expanded true to render the plain type within the label
     */
    public function addPlainType(bool $expanded = false): self
    {
        if ($expanded) {
            $this->updateOption('expanded', true);
        }

        return $this->notRequired()->add(PlainType::class);
    }

    /**
     * Adds a post-set-data-submit event listener.
     * The FormEvents::POST_SET_DATA event is dispatched at the end of the Form::setData() method.
     *
     * @param callable $listener the event listener
     * @param int      $priority The priority of the listener. Listeners
     *                           with a higher priority are called before
     *                           listeners with a lower priority.
     */
    public function addPostSetDataListener(callable $listener, int $priority = 0): self
    {
        return $this->addEventListener(FormEvents::POST_SET_DATA, $listener, $priority);
    }

    /**
     * Adds a post-submit event listener.
     * The FormEvents::POST_SUBMIT event is dispatched at the very end of the Form::submit().
     *
     * @param callable $listener the event listener
     * @param int      $priority The priority of the listener. Listeners
     *                           with a higher priority are called before
     *                           listeners with a lower priority.
     */
    public function addPostSubmitListener(callable $listener, int $priority = 0): self
    {
        return $this->addEventListener(FormEvents::POST_SUBMIT, $listener, $priority);
    }

    /**
     * Adds a pre-set-data event listener.
     *
     * The FormEvents::PRE_SET_DATA event is dispatched at the beginning of the Form::setData() method.
     *
     * @param callable $listener the event listener
     * @param int      $priority The priority of the listener. Listeners
     *                           with a higher priority are called before
     *                           listeners with a lower priority.
     */
    public function addPreSetDataListener(callable $listener, int $priority = 0): self
    {
        return $this->addEventListener(FormEvents::PRE_SET_DATA, $listener, $priority);
    }

    /**
     * Adds a pre-submit event listener.
     *
     * The PRE_SUBMIT event is dispatched at the beginning of the Form::submit() method.
     *
     * @param callable $listener the event listener
     * @param int      $priority The priority of the listener. Listeners
     *                           with a higher priority are called before
     *                           listeners with a lower priority.
     */
    public function addPreSubmitListener(callable $listener, int $priority = 0): self
    {
        return $this->addEventListener(FormEvents::PRE_SUBMIT, $listener, $priority);
    }

    /**
     * Add an repeat password type to the builder and reset all values to default.
     *
     * @param string $passwordLabel the label used for the password
     * @param string $confirmLabel  the label used for the confim password
     */
    public function addRepeatPasswordType(string $passwordLabel = 'user.password.label', string $confirmLabel = 'user.password.confirmation'): self
    {
        if ('user.password.label' !== $passwordLabel) {
            $first_options = \array_replace_recursive(RepeatPasswordType::getFirstOptions(),
                ['label' => $passwordLabel]);
            $this->updateOption('first_options', $first_options);
        }
        if ('user.password.confirmation' !== $confirmLabel) {
            $second_options = \array_replace_recursive(RepeatPasswordType::getSecondOptions(),
                ['label' => $confirmLabel]);
            $this->updateOption('second_options', $second_options);
        }

        return $this->add(RepeatPasswordType::class);
    }

    /**
     * Add a text area type to the builder and reset all values to default.
     */
    public function addTextareaType(): self
    {
        return $this->updateAttribute('rows', 4)
            ->widgetClass('resizable')
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
     * Add an Url type to the builder and reset all values to default.
     *
     * @param string $default_protocol If a value is submitted that doesn't begin with some protocol (e.g. http://, ftp://, etc), this protocol will be prepended to the string when the data is submitted to the form.
     */
    public function addUrlType(?string $default_protocol = 'https'): self
    {
        $this->updateOption('default_protocol', $default_protocol, true);

        return $this->add(UrlType::class);
    }

    /**
     * Add an user list type to the builder and reset all values to default.
     *
     * This type display a drop-down list of user entities.
     */
    public function addUserListType(): self
    {
        return $this->add(UserListType::class);
    }

    /**
     * Adds a Vich image type and reset all values to default.
     */
    public function addVichImageType(): self
    {
        //see https://github.com/kartik-v/bootstrap-fileinput
        $this->updateOption('translation_domain', 'messages')
            ->updateAttribute('accept', 'image/gif,image/jpeg,image/png,image/bmp')
            //->updateAttribute('accept', 'image/*')
            ->updateOption('download_uri', false)
            ->notRequired();

        // labels
        if (!isset($this->options['delete_label'])) {
            $this->updateOption('delete_label', false);
        }

        return $this->add(VichImageType::class);
    }

    /**
     * Add a Yes/No choice type to the builder and reset all values to default.
     */
    public function addYesNoType(): self
    {
        return $this->add(YesNoType::class);
    }

    /**
     * Sets the autocomplete attribute.
     *
     * For Google Chrome, if You want to disable the auto-complete set a random string as attribute like 'nope'.
     *
     * @param string|bool $autocomplete the autocomplete ('on'/'off') or false to remove
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/autocomplete
     */
    public function autocomplete($autocomplete): self
    {
        $autocomplete = empty($autocomplete) ? null : $autocomplete;

        return $this->updateAttribute('autocomplete', $autocomplete);
    }

    /**
     * Sets auto-focus attribute.
     */
    public function autofocus(): self
    {
        return $this->updateAttribute('autofocus', true);
    }

    /**
     * Creates the form within the underlaying form builder.
     *
     * @return FormInterface the form
     *
     * @see FormBuilderInterface::getForm()
     * @see FormHelper::createView()
     */
    public function createForm(): FormInterface
    {
        return $this->builder->getForm();
    }

    /**
     * Create the form view.
     *
     * @return FormView the form view
     *
     * @see FormInterface::createView()
     * @see FormHelper::createForm()
     */
    public function createView(FormView $parent = null): FormView
    {
        return $this->createForm()->createView($parent);
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
     * @param string $domain the translation domain or null for default
     */
    public function domain(?string $domain): self
    {
        $domain = empty($domain) ? null : $domain;

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
     * Gets the form builder.
     */
    public function getBuilder(): FormBuilderInterface
    {
        return $this->builder;
    }

    /**
     * Gets the currency symbol for the given locale.
     *
     * @param string|null $locale the locale to use or null to use the default locale
     */
    public function getCurrencySymbol(string $locale = null): string
    {
        if (null === $locale) {
            $locale = \Locale::getDefault();
        }
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

        return $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
    }

    /**
     * Gets the percent symbol for the given locale.
     *
     * @param string|null $locale the locale to use or null to use the default locale
     */
    public function getPercentSymbol(string $locale = null): string
    {
        if (null === $locale) {
            $locale = \Locale::getDefault();
        }
        $formatter = new \NumberFormatter((string) $locale, \NumberFormatter::PERCENT);

        return $formatter->getSymbol(\NumberFormatter::PERCENT_SYMBOL);
    }

    /**
     * Sets the help property.
     *
     * @param string $help the help identifier to translate
     */
    public function help(?string $help): self
    {
        $help = empty($help) ? null : $help;

        return $this->updateOption('help', $help);
    }

    /**
     * Add a class name to the help class attributes.
     *
     * @param string $name one or more space-separated classes to be added to the help class attribute
     */
    public function helpClass(string $name): self
    {
        return $this->addClasses($this->helpAttributes, $name);
    }

    /**
     * Hides the label.
     */
    public function hideLabel(): self
    {
        return $this->updateOption('label', false);
    }

    /**
     * Sets the label property.
     *
     * @param string $label the label identifier to translate
     */
    public function label(?string $label): self
    {
        $label = empty($label) ? null : $label;

        return $this->updateOption('label_format', $label);
    }

    /**
     * Add a class name to the label class attributes.
     *
     * @param string $name one or more space-separated classes to be added to the label class attribute
     */
    public function labelClass(string $name): self
    {
        return $this->addClasses($this->labelAttributes, $name);
    }

    /**
     * Sets the maximum length.
     *
     * @param int $maxLength the maximum length or 0 if none
     */
    public function maxLength(int $maxLength): self
    {
        return $this->updateAttribute('maxLength', $maxLength > 0 ? $maxLength : null);
    }

    /**
     * Sets the minimum length.
     *
     * @param int $minLength the minimum length or 0 if none
     */
    public function minLength(int $minLength): self
    {
        return $this->updateAttribute('minLength', $minLength > 0 ? $minLength : null);
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
        return $this->updateOption('required', false);
    }

    /**
     * Sets the percent symbol visibility.
     *
     * @param bool $visible true to display the percent symbol; false to hide
     */
    public function percent(bool $visible): self
    {
        return $this->updateOption('symbol', $visible ? $this->getPercentSymbol() : false);
    }

    /**
     * Sets the priority.
     *
     * @param int $priority the priority to set. Fields with higher priorities are rendered first and fields with same priority are rendered in their original order.
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
     * Reset all options and attributes to the default values.
     */
    public function reset(): self
    {
        $this->options = [];
        $this->attributes = [];
        $this->rowAttributes = [];
        $this->helpAttributes = [];
        $this->labelAttributes = [];

        return $this;
    }

    /**
     * Add a class name to the row class attributes.
     *
     * @param string $name one or more space-separated classes to be added to the row class attribute
     */
    public function rowClass(string $name): self
    {
        return $this->addClasses($this->rowAttributes, $name);
    }

    /**
     * Sets the tab index.
     *
     * @param int $index the index or null to remove
     */
    public function tabindex(?int $index): self
    {
        $index = \is_int($index) ? $index : null;

        return $this->updateAttribute('tabIndex', $index);
    }

    /**
     * Updates an attribute.
     *
     * @param string $name  the attribute name
     * @param mixed  $value the attribute value or null to remove
     * @param bool   $force true to put the attribute, even if the value is null
     */
    public function updateAttribute(string $name, $value, bool $force = false): self
    {
        return $this->updateEntry($this->attributes, $name, $value, $force);
    }

    /**
     * Updates a help attribute.
     *
     * @param string $name  the attribute name
     * @param mixed  $value the attribute value
     * @param bool   $force true to put the attribute, even if the value is null
     */
    public function updateHelpAttribute(string $name, $value, bool $force = false): self
    {
        return $this->updateEntry($this->helpAttributes, $name, $value, $force);
    }

    /**
     * Updates a label attribute.
     *
     * @param string $name  the attribute name
     * @param mixed  $value the attribute value
     * @param bool   $force true to put the attribute, even if the value is null
     */
    public function updateLabelAttribute(string $name, $value, bool $force = false): self
    {
        return $this->updateEntry($this->labelAttributes, $name, $value, $force);
    }

    /**
     * Updates an option.
     *
     * @param string $name  the option name
     * @param mixed  $value the option value
     * @param bool   $force true to put the option, even if the value is null
     */
    public function updateOption(string $name, $value, bool $force = false): self
    {
        return $this->updateEntry($this->options, $name, $value, $force);
    }

    /**
     * Updates a row attribute.
     *
     * @param string $name  the attribute name
     * @param mixed  $value the attribute value
     * @param bool   $force true to put the attribute, even if the value is null
     */
    public function updateRowAttribute(string $name, $value, bool $force = false): self
    {
        return $this->updateEntry($this->rowAttributes, $name, $value, $force);
    }

    /**
     * Add a class name to the widget class attribute.
     *
     * @param string $name one or more space-separated classes to be added to the widget class attribute
     */
    public function widgetClass(string $name): self
    {
        return $this->addClasses($this->attributes, $name);
    }

    /**
     * Add one or more classes.
     *
     * @param array  $array the array attributes where to find and update existing classes
     * @param string $name  one or more space-separated classes to add
     */
    private function addClasses(array &$array, string $name): self
    {
        if ('' === \trim($name)) {
            return $this;
        }

        $newValues = \array_filter(\explode(' ', $name));
        $oldValues = \array_filter(\explode(' ', $array['class'] ?? ''));
        $className = \implode(' ', \array_unique(\array_merge($oldValues, $newValues)));

        return $this->updateEntry($array, 'class', '' === $className ? null : $className, false);
    }

    /**
     * Update an entry in the given array.
     *
     * @param array  $array the array to update
     * @param string $name  the entry name
     * @param mixed  $value the entry value
     * @param bool   $force true to put the entry, even if the value is null
     */
    private function updateEntry(array &$array, string $name, $value, bool $force): self
    {
        if (null !== $value || $force) {
            $array[$name] = $value;
        } else {
            unset($array[$name]);
        }

        return $this;
    }
}
