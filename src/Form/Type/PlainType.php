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
use App\Util\FormatUtils;
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
 * @psalm-suppress PropertyNotSetInConstructor
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
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        /** @psalm-var mixed $data */
        $data = $form->getViewData();
        $value = $this->getDataValue($data, $options);
        $display_value = $this->getDisplayValue($data, $options) ?? $value;

        $view->vars = \array_replace(
            $view->vars,
            [
                'value' => $value,
                'display_value' => $display_value,
                'expanded' => $options['expanded'],
                'hidden_input' => $options['hidden_input'],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
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
     * Formats the given value as date.
     *
     * @param \DateTimeInterface|int|null $value   the value to transform
     * @param array                       $options the options
     */
    private function formatDate(\DateTimeInterface|int|null $value, array $options): string
    {
        $timezone = $this->getOptionString($options, 'time_zone');
        $pattern = $this->getOptionString($options, 'date_pattern');
        $calendar = $this->getOptionInt($options, 'calendar', self::CALENDAR_GREGORIAN);
        $date_type = $this->getOptionInt($options, 'date_format', FormatUtils::getDateType());
        $time_type = $this->getOptionInt($options, 'time_format', FormatUtils::getTimeType());

        return (string) FormatUtils::formatDateTime($value, $date_type, $time_type, $timezone, $calendar, $pattern);
    }

    /**
     * Formats the given value as number.
     *
     * @param float|int|string $value   the value to transform
     * @param array            $options the options
     *
     * @return string the formatted number
     */
    private function formatNumber(float|int|string $value, array $options): string
    {
        $type = $this->getOptionString($options, 'number_pattern', '');

        switch ($type) {
            case self::NUMBER_IDENTIFIER:
                return FormatUtils::formatId((int) $value);
            case self::NUMBER_INTEGER:
                return FormatUtils::formatInt((int) $value);
            case self::NUMBER_PERCENT:
                $includeSign = $this->getOptionBool($options, 'percent_sign', true);
                $decimals = $this->getOptionInt($options, 'percent_decimals', 2);
                $roundingMode = $this->getOptionInt($options, 'percent_rounding_mode', \NumberFormatter::ROUND_HALFEVEN);

                return FormatUtils::formatPercent((float) $value, $includeSign, $decimals, $roundingMode);
            case self::NUMBER_AMOUNT:
                return FormatUtils::formatAmount((float) $value);
            default:
                return (string) $value;
        }
    }

    /**
     * Transform the given value as string.
     *
     * @param mixed $value   the value to transform
     * @param array $options the options
     *
     * @throws TransformationFailedException if the value can not be mapped to a string
     */
    private function getDataValue(mixed $value, array $options): string
    {
        // transformer?
        /** @var callable|null $callback */
        $callback = $options['value_transformer'] ?? null;
        if (\is_callable($callback)) {
            /** @var mixed $value */
            $value = \call_user_func($callback, $value);
        }

        // boolean?
        if (true === $value) {
            return $this->trans('common.value_true');
        }
        if (false === $value) {
            return $this->trans('common.value_false');
        }

        // value?
        if (null === $value || '' === $value) {
            /** @var callable|null $callback */
            $callback = $options['empty_value'] ?? null;
            if (\is_callable($callback)) {
                return (string) \call_user_func($callback, $value);
            }

            return (string) $this->getOptionString($options, 'empty_value', 'common.value_null', true);
        }

        // array?
        if (\is_array($value)) {
            $callback = fn (mixed $item): string => $this->getDataValue($item, $options);
            $values = \array_map($callback, $value);
            $separator = $this->getOptionString($options, 'separator', ', ');

            return \implode((string) $separator, $values);
        }

        // entity?
        if ($value instanceof AbstractEntity) {
            return $value->getDisplay();
        }

        // date?
        if ($value instanceof \DateTimeInterface) {
            return $this->formatDate($value, $options);
        }

        // numeric?
        if (\is_numeric($value)) {
            return $this->formatNumber($value, $options);
        }

        // to string?
        if (\is_scalar($value) || (\is_object($value) && \method_exists($value, '__toString'))) {
            return (string) $value;
        }

        // error
        throw new TransformationFailedException(\sprintf('Unable to map the instance of "%s" to a string.', \get_debug_type($value)));
    }

    private function getDisplayValue(mixed $value, array $options): ?string
    {
        /** @var callable|null $callback */
        $callback = $options['display_transformer'] ?? null;
        if (\is_callable($callback)) {
            return (string) \call_user_func($callback, $value);
        }

        return null;
    }

    /**
     * Gets the boolean value from the array options.
     *
     * @param array  $options      the array options
     * @param string $name         the option name
     * @param bool   $defaultValue the default value if option is not set
     *
     * @return bool the option value
     */
    private function getOptionBool(array $options, string $name, bool $defaultValue): bool
    {
        if (isset($options[$name]) && \is_bool($options[$name])) {
            return $options[$name];
        }

        return $defaultValue;
    }

    /**
     * Gets the integer value from the array options.
     *
     * @param array  $options      the array options
     * @param string $name         the option name
     * @param int    $defaultValue the default value if option is not set
     *
     * @return int the option value
     */
    private function getOptionInt(array $options, string $name, int $defaultValue): int
    {
        if (isset($options[$name]) && \is_int($options[$name])) {
            return $options[$name];
        }

        return $defaultValue;
    }

    /**
     * Gets the string value from the array options.
     *
     * @param array   $options      the array options
     * @param string  $name         the option name
     * @param ?string $defaultValue the default value if option is not set
     * @param bool    $translate    true to translate the default value
     *
     * @return string|null the option value
     */
    private function getOptionString(array $options, string $name, ?string $defaultValue = null, bool $translate = false): ?string
    {
        $value = isset($options[$name]) && \is_string($options[$name]) ? $options[$name] : $defaultValue;

        return $translate ? $this->trans((string) $value) : $value;
    }
}
