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
 *     separator: string,
 *     text_class: string|null,
 *     percent_sign: bool,
 *     percent_decimals: int,
 *     percent_rounding_mode: \NumberFormatter::ROUND_*,
 *     number_pattern: self::NUMBER_*|null,
 *     date_format: self::FORMAT_*|null,
 *     time_format: self::FORMAT_*|null,
 *     date_pattern: string|null,
 *     time_zone: string|null,
 *     calendar: self::CALENDAR_*,
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
        $view->vars['text_class'] = $options['text_class'];
        $view->vars['hidden_input'] = $options['hidden_input'];
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
            'calendar' => self::CALENDAR_GREGORIAN,
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
    private function formatArray(array $value, array $options): string
    {
        $separator = $options['separator'];
        $values = \array_map(fn (mixed $item): string => $this->getDataValue($item, $options), $value);

        return \implode($separator, $values);
    }

    private function formatBool(bool $value): string
    {
        return $value ? $this->trans('common.value_true') : $this->trans('common.value_false');
    }

    /**
     * @psalm-param OptionsType $options
     */
    private function formatDate(\DateTimeInterface|int|null $value, array $options): string
    {
        $calendar = $options['calendar'];
        $timezone = $options['time_zone'];
        $pattern = $options['date_pattern'];
        $date_type = $options['date_format'];
        $time_type = $options['time_format'];

        return (string) FormatUtils::formatDateTime($value, $date_type, $time_type, $timezone, $calendar, $pattern);
    }

    /**
     * @psalm-param OptionsType $options
     */
    private function formatEmpty(mixed $value, array $options): string
    {
        if (\is_callable($options['empty_value'])) {
            return \call_user_func($options['empty_value'], $value);
        }

        return $this->trans($options['empty_value'] ?? 'common.value_null');
    }

    /**
     * @psalm-param OptionsType $options
     */
    private function formatNumber(float|int|string $value, array $options): string
    {
        $type = $options['number_pattern'];

        return match ($type) {
            self::NUMBER_IDENTIFIER => FormatUtils::formatId($value),
            self::NUMBER_INTEGER => FormatUtils::formatInt($value),
            self::NUMBER_AMOUNT => FormatUtils::formatAmount($value),
            self::NUMBER_PERCENT => $this->formatPercent($value, $options),
            default => (string) $value
        };
    }

    /**
     * @psalm-param OptionsType $options
     */
    private function formatPercent(float|int|string $value, array $options): string
    {
        $includeSign = $options['percent_sign'];
        $decimals = $options['percent_decimals'];
        $roundingMode = $options['percent_rounding_mode'];

        return FormatUtils::formatPercent($value, $includeSign, $decimals, $roundingMode);
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

        if (\is_bool($value)) {
            return $this->formatBool($value);
        }

        if (null === $value || '' === $value) {
            return $this->formatEmpty($value, $options);
        }

        if (\is_array($value)) {
            return $this->formatArray($value, $options);
        }

        if ($value instanceof AbstractEntity) {
            return $value->getDisplay();
        }

        if ($value instanceof \DateTimeInterface) {
            return $this->formatDate($value, $options);
        }

        if (\is_numeric($value)) {
            return $this->formatNumber($value, $options);
        }

        if (\is_scalar($value) || (\is_object($value) && \method_exists($value, '__toString'))) {
            return (string) $value;
        }

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
