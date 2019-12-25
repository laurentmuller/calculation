<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Form;

use App\Form\Type\CalculationStateEntityType;
use App\Form\Type\CategoryEntityType;
use App\Form\Type\PlainType;
use App\Form\Type\UserEntityType;
use App\Form\Type\YesNoType;
use Locale;
use NumberFormatter;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;

/**
 * Helper class to add types to a form builder.
 *
 * @author Laurent Muller
 */
class FormHelper
{
    /**
     * Constant to add the percent symbol after the input (default).
     */
    const PercentDefault = 'default';

    /**
     * Constant to hide the percent sybmol.
     */
    const PercentHide = 'hide';

    /**
     * Constant to add the percent symbol before the input.
     */
    const PercentPrepend = 'prepend';

    /**
     * The attributes.
     *
     * @var array|mixed|string
     */
    private $attributes = [];

    /**
     * The parent builder.
     *
     * @var FormBuilderInterface
     */
    private $builder;

    /**
     * The field identifier.
     *
     * @var string
     */
    private $field;

    /**
     * The label attributes.
     *
     * @var array
     */
    private $labelAttributes = [];

    /**
     * The options.
     *
     * @var array
     */
    private $options = [];

    /**
     * The row attributes.
     *
     * @var array
     */
    private $rowAttributes = [];

    /**
     * Constructor.
     *
     * @param formBuilderInterface $builder the parent builder
     */
    public function __construct(FormBuilderInterface $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Adds a new field to this builder and reset all values to default.
     *
     * @param string $type the child type to add
     */
    public function add($type): self
    {
        // merge
        if (!empty($this->attributes)) {
            $this->options['attr'] = $this->attributes;
        }
        if (!empty($this->rowAttributes)) {
            $this->options['row_attr'] = $this->rowAttributes;
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
     * Add a category type to the builder and reset all values to default.
     *
     * This type display a drop-down list of Category entities.
     */
    public function addCategoryType(): self
    {
        return $this->add(CategoryEntityType::class);
    }

    /**
     * Add a checkbox type to the builder and reset all values to default.
     *
     * @param bool $switchStyle true to render the checkbox with the toggle switch style
     */
    public function addCheckboxType(bool $switchStyle = true): self
    {
        if ($switchStyle) {
            $this->updateLabelAttribute('class', 'switch-custom');
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
     * @param string $entryType the entry type class
     */
    public function addCollectionType(string $entryType): self
    {
        return $this->updateOption('entry_type', $entryType)
            ->updateOption('by_reference', false)
            ->updateOption('allow_delete', true)
            ->updateOption('allow_add', true)
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
            $this->className('color-picker');
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
     * Add a text area type used within the Summernote editor to the builder and reset all values to default.
     */
    public function addEditorType(): self
    {
        return $this->className('must-validate')
            ->add(TextareaType::class);
    }

    /**
     * Add an email type to the builder and reset all values to default.
     */
    public function addEmailType(): self
    {
        return $this->add(EmailType::class);
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
     * Add a money type to the builder and reset all values to default.
     */
    public function addMoneyType(): self
    {
        return $this->className('text-right')
            ->add(MoneyType::class);
    }

    /**
     * Add a number type to the builder and reset all values to default.
     */
    public function addNumberType(): self
    {
        return $this->className('text-right')
            ->updateAttribute('scale', 2)
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
    public function addPercentType(int $min = PHP_INT_MIN, int $max = PHP_INT_MAX, float $step = 1): self
    {
        $this->className('text-right')
            ->autocomplete('off');
        if (PHP_INT_MIN !== $min) {
            $this->updateAttribute('min', $min);
        }
        if (PHP_INT_MAX !== $max) {
            $this->updateAttribute('max', $max);
        }
        if (-1 !== $step) {
            $this->updateAttribute('step', $step);
        }

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
     * Add a calculation state type to the builder and reset all values to default.
     *
     * This type display a drop-down list of CalculationState entities.
     */
    public function addStateType(): self
    {
        return $this->add(CalculationStateEntityType::class);
    }

    /**
     * Add a text area type to the builder and reset all values to default.
     */
    public function addTextareaType(): self
    {
        return $this->updateAttribute('rows', 4)
            ->className('resizable')
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
     */
    public function addUrlType(): self
    {
        return $this->add(UrlType::class);
    }

    /**
     * Add an user type to the builder and reset all values to default.
     *
     * This type display a drop-down list of user entities.
     */
    public function addUserType(): self
    {
        return $this->add(UserEntityType::class);
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
     * Add a class name.
     *
     * @param string $name the class name to add
     */
    public function className(?string $name): self
    {
        if (!empty($name)) {
            $names = $this->attributes['class'] ?? '';
            if (false === \stripos($names, $name)) {
                $names = \trim($names . ' ' . $name, ' ');

                return $this->updateAttribute('class', $names);
            }
        }

        return $this;
    }

    /**
     * Sets the currency symbol.
     *
     * @param string|bool $currency the currency symbol or false to hide symbol
     */
    public function currency($currency): self
    {
        $currency = (false === $currency || !empty($currency)) ? $currency : null;

        return $this->updateOption('currency', $currency);
    }

    /**
     * Sets the currency symbol to the locale default.
     */
    public function defaultCurrency(): self
    {
        return $this->currency($this->getCurrencySymbol());
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
     * @param string $field the field name
     */
    public function field(string $field): self
    {
        $this->field = $field;

        return $this;
    }

    /**
     * Gets the currency sambol.
     */
    public function getCurrencySymbol(): string
    {
        static $symbol;
        if (!$symbol) {
            $locale = Locale::getDefault();
            $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            $symbol = $formatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
        }

        return $symbol;
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
     * Sets the mapped property.
     *
     * If you wish the field to be ignored when reading or writing to the object, you can set the mapped option to false.
     *
     * @param bool $mapped false if not mapped
     */
    public function mapped(bool $mapped): self
    {
        $mapped = false === $mapped ? $mapped : null;

        return $this->updateOption('mapped', $mapped);
    }

    /**
     * Sets the maximum length.
     *
     * @param int $maxLength the maximum length or 0 if none
     */
    public function maxLength(int $maxLength): self
    {
        $maxLength = (int) $maxLength;

        return $this->updateAttribute('maxLength', $maxLength > 0 ? $maxLength : null);
    }

    /**
     * Sets the minimum length.
     *
     * @param int $minLength the minimum length or 0 if none
     */
    public function minLength(int $minLength): self
    {
        $minLength = (int) $minLength;

        return $this->updateAttribute('minLength', $minLength > 0 ? $minLength : null);
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
        return $this->updateOption('symbol', $visible ? '%' : false);
    }

    /**
     * Sets the read-only property to true.
     */
    public function readonly(): self
    {
        return $this->updateAttribute('readonly', true);
    }

    /**
     * Reset all properties to the default values.
     */
    public function reset(): self
    {
        $this->options = [];
        $this->attributes = [];
        $this->rowAttributes = [];
        $this->labelAttributes = [];

        return $this;
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
     * Updates a label attribute.
     *
     * @param string $name  the attribute name
     * @param mixed  $value the attribute value or null to remove
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
     * @param mixed  $value the option value or null to remove
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
     * @param mixed  $value the attribute value or null to remove
     * @param bool   $force true to put the attribute, even if the value is null
     */
    public function updateRowAttribute(string $name, $value, bool $force = false): self
    {
        return $this->updateEntry($this->rowAttributes, $name, $value, $force);
    }

    /**
     * Update an entry in the given array.
     *
     * @param array  $array the array to update
     * @param string $name  the entry name
     * @param mixed  $value the entry value or null to remove
     * @param bool   $force true to put the entry, even if the value is null
     */
    protected function updateEntry(array &$array, string $name, $value, bool $force): self
    {
        if (null !== $value || $force) {
            $array[$name] = $value;
        } else {
            unset($array[$name]);
        }

        return $this;
    }
}
