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

namespace App\Form\Type;

use App\Entity\AbstractEntity;
use App\Traits\TranslatorTrait;
use App\Util\FormatUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A form type that just renders the field as a span tag.
 *
 * This is useful for forms where certain field need to be shown but not editable.
 * If the 'expanded' option is set to true, a div tag is added around the span tag.
 *
 * @author Laurent Muller
 */
class PlainType extends AbstractType
{
    use TranslatorTrait;

    /**
     * The gregorian calendar type.
     */
    public const CALENDAR_GREGORIAN = 'gregorian';

    /**
     * The traditional calendar type.
     */
    public const CALENDAR_TRADITIONAL = 'traditional';

    /**
     * The full date or time format.
     */
    public const FORMAT_FULL = \IntlDateFormatter::FULL;

    /**
     * The long date or time format.
     */
    public const FORMAT_LONG = \IntlDateFormatter::LONG;

    /**
     * The medium date or time format.
     */
    public const FORMAT_MEDIUM = \IntlDateFormatter::MEDIUM;

    /**
     * The none date or time format.
     */
    public const FORMAT_NONE = \IntlDateFormatter::NONE;

    /**
     * The short date or time format.
     */
    public const FORMAT_SHORT = \IntlDateFormatter::SHORT;

    /**
     * The amount number pattern.
     */
    public const NUMBER_AMOUNT = 'price';

    /**
     * The identifier number pattern.
     */
    public const NUMBER_IDENTIFIER = 'identifier';

    /**
     * The integer number pattern.
     */
    public const NUMBER_INTEGER = 'integer';

    /**
     * The percent number pattern.
     */
    public const NUMBER_PERCENT = 'percent';

    /**
     * Constructor.
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->setTranslator($translator);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        /** @var mixed $data */
        $data = $form->getViewData();
        $view->vars = \array_replace(
            $view->vars,
            [
                'expanded' => $options['expanded'],
                'hidden_input' => $options['hidden_input'],
                'value' => $this->transformValue($data, $options),
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
        $this->configureDate($resolver);
        $this->configureNumber($resolver);

        $resolver->setAllowedTypes('empty_value', [
            'null',
            'string',
            'callable',
        ]);

        $resolver->setAllowedTypes('separator', [
            'null',
            'string',
        ]);

        $resolver->setAllowedTypes('transformer', [
            'null',
            'callable',
        ]);
    }

    private function configureDate(OptionsResolver $resolver): void
    {
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

        $resolver->setAllowedTypes('calendar', [
            'null',
            'string',
        ])->setAllowedValues('calendar', [
            null,
            self::CALENDAR_GREGORIAN,
            self::CALENDAR_TRADITIONAL,
        ]);

        $resolver->setAllowedTypes('date_pattern', [
            'null',
            'string',
        ]);
    }

    private function configureDefaults(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'hidden_input' => false,
            'read_only' => true,
            'disabled' => true,
            'required' => false,
            'date_format' => null,
            'time_format' => null,
            'date_pattern' => null,
            'time_zone' => null,
            'calendar' => null,
            'number_pattern' => null,
            'percent_sign' => true,
            'percent_decimals' => 2,
            'percent_rounding_mode' => \NumberFormatter::ROUND_HALFEVEN,
            'empty_value' => null,
            'compound' => false,
            'expanded' => false,
            'separator' => ', ',
            'transformer' => null,
        ]);
    }

    private function configureNumber(OptionsResolver $resolver): void
    {
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
        $calendar = $this->getCalendarFormat($options);
        $timezone = $this->getOptionString($options, 'time_zone');
        $pattern = $this->getOptionString($options, 'date_pattern');
        $datetype = $this->getOptionInt($options, 'date_format', FormatUtils::getDateType());
        $timetype = $this->getOptionInt($options, 'time_format', FormatUtils::getTimeType());

        return (string) FormatUtils::formatDateTime($value, $datetype, $timetype, $timezone, $calendar, $pattern);
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
     * Gets the calendar format.
     *
     * @param array $options the options
     *
     * @return int the calendar format:
     *             <ul>
     *             <li><code>IntlDateFormatter::GREGORIAN</code></li>
     *             <li><code>IntlDateFormatter::TRADITIONAL</code></li>
     *             </ul>
     */
    private function getCalendarFormat(array $options): int
    {
        $calendar = $this->getOptionString($options, 'calendar', self::CALENDAR_GREGORIAN);

        return self::CALENDAR_GREGORIAN === $calendar ? \IntlDateFormatter::GREGORIAN : \IntlDateFormatter::TRADITIONAL;
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
     * @param array       $options      the array options
     * @param string      $name         the option name
     * @param string|null $defaultValue the default value if option is not set
     * @param bool        $translate    true to translate the default value
     *
     * @return string|null the option value
     */
    private function getOptionString(array $options, string $name, ?string $defaultValue = null, bool $translate = false): ?string
    {
        $value = isset($options[$name]) && \is_string($options[$name]) ? $options[$name] : $defaultValue;

        return $translate ? $this->trans((string) $value) : $value;
    }

    /**
     * Transform the given value as string.
     *
     * @param mixed $value   the value to transform
     * @param array $options the options
     *
     * @throws TransformationFailedException if the value can not be mapped to a string
     */
    private function transformValue(mixed $value, array $options): string
    {
        // transformer?
        /** @var callable|null $callback */
        $callback = $options['transformer'] ?? null;
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
            $callback = function (mixed $item) use ($options): string {
                return $this->transformValue($item, $options);
            };
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
}
