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
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

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
            'empty_value' => null,
            'compound' => false,
            'expanded' => false,
            'separator' => ', ',
            'transformer' => null,
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

        $resolver->setAllowedTypes('empty_value', [
            'null',
            'string',
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

    /**
     * Formats the given value as date.
     *
     * @param mixed $value   the value to transform
     * @param array $options the options
     *
     * @return string|null the formatted date
     */
    private function formatDate($value, array $options): ?string
    {
        $calendar = $this->getCalendarFormat($options);
        $timezone = $this->getOptionString($options, 'time_zone');
        $pattern = $this->getOptionString($options, 'date_pattern');
        $datetype = $this->getOptionInt($options, 'date_format', FormatUtils::getDateType());
        $timetype = $this->getOptionInt($options, 'time_format', FormatUtils::getTimeType());

        return FormatUtils::formatDateTime($value, $datetype, $timetype, $timezone, $calendar, $pattern);
    }

    /**
     * Formats the given value as number.
     *
     * @param mixed $value   the value to transform
     * @param array $options the options
     *
     * @return string the formatted number
     */
    private function formatNumber($value, array $options): string
    {
        $type = $this->getOptionString($options, 'number_pattern', '');
        switch ($type) {
            case self::NUMBER_IDENTIFIER:
                return FormatUtils::formatId($value);
            case self::NUMBER_INTEGER:
                return FormatUtils::formatInt($value);
            case self::NUMBER_PERCENT:
                return FormatUtils::formatPercent($value, true);
            case self::NUMBER_AMOUNT:
                return FormatUtils::formatAmount($value);
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
            return (int) $options[$name];
        }

        return $defaultValue;
    }

    /**
     * Gets the string value from the array options.
     *
     * @param array  $options      the array options
     * @param string $name         the option name
     * @param string $defaultValue the default value if option is not set
     * @param bool   $translate    true to translate the default value
     *
     * @return string the option value
     */
    private function getOptionString(array $options, string $name, ?string $defaultValue = null, bool $translate = false): ?string
    {
        $value = isset($options[$name]) && \is_string($options[$name]) ? $options[$name] : $defaultValue;

        return $translate ? $this->trans($value) : $value;
    }

    /**
     * Transform the given value as string.
     *
     * @param mixed $value   the value to transform
     * @param array $options the options
     *
     * @return string|null the transformed value
     *
     * @throws TransformationFailedException if the value can not be mapped to a string
     */
    private function transformValue($value, array $options): ?string
    {
        // transformer?
        if (isset($options['transformer']) && \is_callable($options['transformer'])) {
            $transformer = $options['transformer'];
            $value = \call_user_func($transformer, $value);
        }

        // boolean?
        if (true === $value) {
            return $this->trans('common.value_true');
        }
        if (false === $value) {
            return $this->trans('common.value_false');
        }

        // value?
        if (null === $value || (\is_string($value) && '' === $value)) {
            return $this->getOptionString($options, 'empty_value', 'common.value_null', true);
        }

        // array?
        if (\is_array($value)) {
            // @phpstan-ignore-next-line
            $callback = function ($item) use ($options): ?string {
                return $this->transformValue($item, $options);
            };
            $values = \array_map($callback, (array) $value);
            $separator = $this->getOptionString($options, 'separator', ', ');

            return \implode($separator, $values);
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
        throw new TransformationFailedException(\sprintf('Unable to map the instance of "%s" to a string.', get_debug_type($value)));
    }
}
