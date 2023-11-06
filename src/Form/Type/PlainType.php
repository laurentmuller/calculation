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

namespace App\Form\Type;

use App\Entity\AbstractEntity;
use App\Traits\TranslatorAwareTrait;
use App\Utils\FormatUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * A form type that just renders the field as a span tag.
 *
 * This is useful for forms where certain field need to be shown but not editable.
 * If the 'expanded' option is set to true, a div tag is added around the span tag.
 *
 * @extends AbstractType<mixed>
 *
 * @psalm-type OptionsType = array{
 *     hidden_input: bool,
 *     read_only: bool,
 *     disabled: bool,
 *     required: bool,
 *     expanded: bool,
 *     text_class: string|null,
 *     percent_sign: bool,
 *     percent_decimals: int,
 *     percent_rounding_mode: \NumberFormatter::ROUND_*,
 *     number_pattern: self::NUMBER_*|null,
 *     date_format: self::FORMAT_*|null,
 *     time_format: self::FORMAT_*|null,
 *     date_pattern: string|null,
 *     time_zone: string|null,
 *     calendar: self::CALENDAR_*|null,
 *     empty_value: callable(mixed):string|string|null,
 *     display_transformer: callable(mixed):string|null,
 *     value_transformer: callable(mixed):mixed|null,
 *     ...}
 */
class PlainType extends AbstractType implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;
    use TranslatorAwareTrait;

    /**
     * The gregorian calendar type.
     */
    final public const CALENDAR_GREGORIAN = \IntlDateFormatter::GREGORIAN;

    /**
     * The traditional calendar type.
     */
    final public const CALENDAR_TRADITIONAL = \IntlDateFormatter::TRADITIONAL;

    /**
     * The full date or time format.
     */
    final public const FORMAT_FULL = \IntlDateFormatter::FULL;

    /**
     * The long date or time format.
     */
    final public const FORMAT_LONG = \IntlDateFormatter::LONG;

    /**
     * The medium date or time format.
     */
    final public const FORMAT_MEDIUM = \IntlDateFormatter::MEDIUM;

    /**
     * The none date or time format.
     */
    final public const FORMAT_NONE = \IntlDateFormatter::NONE;

    /**
     * The short date or time format.
     */
    final public const FORMAT_SHORT = \IntlDateFormatter::SHORT;

    /**
     * The amount number pattern.
     */
    final public const NUMBER_AMOUNT = 'price';

    /**
     * The identifier number pattern.
     */
    final public const NUMBER_IDENTIFIER = 'identifier';

    /**
     * The integer number pattern.
     */
    final public const NUMBER_INTEGER = 'integer';

    /**
     * The percent number pattern.
     */
    final public const NUMBER_PERCENT = 'percent';

    /**
     * @psalm-suppress InvalidPropertyAssignmentValue
     *
     * @psalm-param OptionsType $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @psalm-var mixed $data */
        $data = $form->getViewData();
        $value = $this->getDataValue($data, $options);
        $display_value = $this->getDisplayValue($data, $options) ?? $value;

        $view->vars['value'] = $value;
        $view->vars['display_value'] = $display_value;
        $view->vars['expanded'] = $options['expanded'];
        $view->vars['hidden_input'] = $options['hidden_input'];
        $view->vars['text_class'] = $options['text_class'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $this->configureDefaults($resolver);
        $this->configureNumber($resolver);
        $this->configureDate($resolver);
    }

    private function configureDate(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'date_format' => null,
            'time_format' => null,
            'date_pattern' => null,
            'time_zone' => null,
            'calendar' => null,
        ]);

        $resolver->setAllowedTypes('date_format', [
            'null',
            'int',
        ])->setAllowedValues('date_format', [
            null,
            self::FORMAT_FULL,
            self::FORMAT_LONG,
            self::FORMAT_MEDIUM,
            self::FORMAT_SHORT,
            self::FORMAT_NONE,
        ]);

        $resolver->setAllowedTypes('time_format', [
            'null',
            'int',
        ])->setAllowedValues('time_format', [
            null,
            self::FORMAT_FULL,
            self::FORMAT_LONG,
            self::FORMAT_MEDIUM,
            self::FORMAT_SHORT,
            self::FORMAT_NONE,
        ]);

        $resolver->setAllowedTypes('date_pattern', [
            'null',
            'string',
        ]);

        $resolver->setAllowedTypes('time_zone', [
            'null',
            'string',
        ]);

        $resolver->setAllowedTypes('calendar', [
            'null',
            'int',
        ])->setAllowedValues('calendar', [
            null,
            self::CALENDAR_GREGORIAN,
            self::CALENDAR_TRADITIONAL,
        ]);
    }

    private function configureDefaults(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'hidden_input' => false,
            'read_only' => true,
            'disabled' => true,
            'required' => false,
            'expanded' => false,
            'empty_value' => null,
            'compound' => false,
            'separator' => ', ',
            'value_transformer' => null,
            'display_transformer' => null,
            'text_class' => null,
        ]);

        $resolver->setAllowedTypes('hidden_input', 'bool')
            ->setAllowedTypes('read_only', 'bool')
            ->setAllowedTypes('disabled', 'bool')
            ->setAllowedTypes('required', 'bool')
            ->setAllowedTypes('expanded', 'bool');

        $resolver->setAllowedTypes('empty_value', [
            'null',
            'string',
            'callable',
        ]);

        $resolver->setAllowedTypes('separator', [
            'null',
            'string',
        ]);

        $resolver->setAllowedTypes('value_transformer', [
            'null',
            'callable',
        ]);

        $resolver->setAllowedTypes('display_transformer', [
            'null',
            'callable',
        ]);

        $resolver->setAllowedTypes('text_class', [
            'null',
            'string',
        ]);
    }

    private function configureNumber(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'number_pattern' => null,
            'percent_sign' => true,
            'percent_decimals' => 2,
            'percent_rounding_mode' => \NumberFormatter::ROUND_HALFEVEN,
        ]);

        $resolver->setAllowedTypes('number_pattern', [
            'null',
            'string',
        ])->setAllowedValues('number_pattern', [
            null,
            self::NUMBER_IDENTIFIER,
            self::NUMBER_INTEGER,
            self::NUMBER_PERCENT,
            self::NUMBER_AMOUNT,
        ]);

        $resolver->setAllowedTypes('percent_sign', 'bool')
            ->setAllowedTypes('percent_decimals', 'int')
            ->setAllowedTypes('percent_rounding_mode', 'int')
            ->setAllowedValues('percent_rounding_mode', [
                \NumberFormatter::ROUND_CEILING,
                \NumberFormatter::ROUND_FLOOR,
                \NumberFormatter::ROUND_DOWN,
                \NumberFormatter::ROUND_UP,
                \NumberFormatter::ROUND_HALFEVEN,
                \NumberFormatter::ROUND_HALFDOWN,
                \NumberFormatter::ROUND_HALFUP,
            ]);
    }

    /**
     * @psalm-param OptionsType $options
     */
    private function formatPercent(float|int|string $value, array $options): string
    {
        $includeSign = $this->isOptionBool($options, 'percent_sign', true);
        $decimals = $this->getOptionInt($options, 'percent_decimals', 2);
        $roundingMode = $this->getOptionInt($options, 'percent_rounding_mode', \NumberFormatter::ROUND_HALFEVEN);

        return FormatUtils::formatPercent((float) $value, $includeSign, $decimals, $roundingMode);
    }

    /**
     * @throws TransformationFailedException if the value can not be mapped to a string
     *
     * @psalm-param OptionsType $options
     */
    private function getDataValue(mixed $value, array $options): string
    {
        // transform callback
        /** @psalm-var mixed $value */
        $value = $this->transformValue($value, $options);

        // boolean?
        if (\is_bool($value)) {
            return $this->transformBool($value);
        }

        // empty?
        if (null === $value || '' === $value) {
            return $this->transformEmpty($value, $options);
        }

        // array?
        if (\is_array($value)) {
            return $this->transformArray($value, $options);
        }

        // entity?
        if ($value instanceof AbstractEntity) {
            return $value->getDisplay();
        }

        // date?
        if ($value instanceof \DateTimeInterface) {
            return $this->transformDate($value, $options);
        }

        // numeric?
        if (\is_numeric($value)) {
            return $this->transformNumber($value, $options);
        }

        // to string?
        if (\is_scalar($value) || (\is_object($value) && \method_exists($value, '__toString'))) {
            return (string) $value;
        }

        // error
        throw new TransformationFailedException(\sprintf('Unable to map the instance of "%s" to a string.', \get_debug_type($value)));
    }

    /**
     * @psalm-param OptionsType $options
     */
    private function getDisplayValue(mixed $value, array $options): ?string
    {
        if (\is_callable($options['display_transformer'])) {
            return \call_user_func($options['display_transformer'], $value);
        }

        return null;
    }

    private function getOptionInt(array $options, string $name, int $defaultValue): int
    {
        if (isset($options[$name]) && \is_int($options[$name])) {
            return $options[$name];
        }

        return $defaultValue;
    }

    /**
     * @psalm-return ($defaultValue is null ? (string|null) : string)
     */
    private function getOptionString(array $options, string $name, string $defaultValue = null, bool $translate = false): ?string
    {
        $value = isset($options[$name]) && \is_string($options[$name]) ? $options[$name] : $defaultValue;

        return $translate ? $this->trans((string) $value) : $value;
    }

    private function isOptionBool(array $options, string $name, bool $defaultValue): bool
    {
        if (isset($options[$name]) && \is_bool($options[$name])) {
            return $options[$name];
        }

        return $defaultValue;
    }

    /**
     * @psalm-param OptionsType $options
     */
    private function transformArray(array $value, array $options): string
    {
        $callback = fn (mixed $item): string => $this->getDataValue($item, $options);
        $values = \array_map($callback, $value);
        $separator = $this->getOptionString($options, 'separator', ', ');

        return \implode($separator, $values);
    }

    private function transformBool(bool $value): string
    {
        return $value ? $this->trans('common.value_true') : $this->trans('common.value_false');
    }

    /**
     * @psalm-param OptionsType $options
     */
    private function transformDate(\DateTimeInterface|int|null $value, array $options): string
    {
        $timezone = $this->getOptionString($options, 'time_zone');
        $pattern = $this->getOptionString($options, 'date_pattern');
        $calendar = $this->getOptionInt($options, 'calendar', self::CALENDAR_GREGORIAN);
        $date_type = $this->getOptionInt($options, 'date_format', FormatUtils::getDateType());
        $time_type = $this->getOptionInt($options, 'time_format', FormatUtils::getTimeType());

        return (string) FormatUtils::formatDateTime($value, $date_type, $time_type, $timezone, $calendar, $pattern);
    }

    /**
     * @psalm-param OptionsType $options
     */
    private function transformEmpty(mixed $value, array $options): string
    {
        if (\is_callable($options['empty_value'])) {
            return \call_user_func($options['empty_value'], $value);
        }

        return $this->getOptionString($options, 'empty_value', 'common.value_null', true);
    }

    /**
     * @psalm-param OptionsType $options
     */
    private function transformNumber(float|int|string $value, array $options): string
    {
        $type = $this->getOptionString($options, 'number_pattern', '');

        return match ($type) {
            self::NUMBER_IDENTIFIER => FormatUtils::formatId((int) $value),
            self::NUMBER_INTEGER => FormatUtils::formatInt((int) $value),
            self::NUMBER_AMOUNT => FormatUtils::formatAmount((float) $value),
            self::NUMBER_PERCENT => $this->formatPercent($value, $options),
            default => (string) $value
        };
    }

    /**
     * @psalm-param OptionsType $options
     */
    private function transformValue(mixed $value, array $options): mixed
    {
        if (\is_callable($options['value_transformer'])) {
            return \call_user_func($options['value_transformer'], $value);
        }

        return $value;
    }
}
