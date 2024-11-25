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

use App\Interfaces\EntityInterface;
use App\Utils\FormatUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A form type that just renders the field as a span tag.
 *
 * This is useful for forms where certain field needs to be shown but not editable.
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
 *     empty_value: callable(mixed):string|string|null,
 *     display_transformer: (callable(mixed):(string|null))|null,
 *     value_transformer: (callable(mixed):mixed)|null,
 *     ...}
 */
class PlainType extends AbstractType
{
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

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * @psalm-param OptionsType $options
     *
     * @psalm-suppress InvalidPropertyAssignmentValue
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @psalm-var mixed $data */
        $data = $form->getViewData();
        $value = $this->getDataValue($data, $options);
        $display_value = $this->getDisplayValue($data, $options, $value);

        $view->vars = \array_replace($view->vars, [
            'value' => $value,
            'display_value' => $display_value,
            'expanded' => $options['expanded'],
            'text_class' => $options['text_class'],
            'hidden_input' => $options['hidden_input'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $this->configureDefaults($resolver);
        $this->configureNumber($resolver);
        $this->configureDate($resolver);
    }

    private function configureDate(OptionsResolver $resolver): void
    {
        $allowedValues = [
            null,
            self::FORMAT_FULL,
            self::FORMAT_LONG,
            self::FORMAT_MEDIUM,
            self::FORMAT_SHORT,
            self::FORMAT_NONE,
        ];

        $resolver->define('date_format')
            ->default(null)
            ->allowedTypes('null', 'int')
            ->allowedValues(...$allowedValues);

        $resolver->define('time_format')
            ->default(null)
            ->allowedTypes('null', 'int')
            ->allowedValues(...$allowedValues);

        $resolver->define('date_pattern')
            ->default(null)
            ->allowedTypes('null', 'string');
    }

    private function configureDefaults(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'disabled' => true,
            'compound' => false,
            'required' => false,
        ]);

        $resolver->define('hidden_input')
            ->default(false)
            ->allowedTypes('bool');

        $resolver->define('read_only')
            ->default(true)
            ->allowedTypes('bool');

        $resolver->define('expanded')
            ->default(false)
            ->allowedTypes('bool');

        $resolver->define('empty_value')
            ->default(null)
            ->allowedTypes('null', 'string', 'callable');

        $resolver->define('separator')
            ->default(', ')
            ->allowedTypes('string');

        $resolver->define('value_transformer')
            ->default(null)
            ->allowedTypes('null', 'callable');

        $resolver->define('display_transformer')
            ->default(null)
            ->allowedTypes('null', 'callable');

        $resolver->define('text_class')
            ->default(null)
            ->allowedTypes('null', 'string');
    }

    private function configureNumber(OptionsResolver $resolver): void
    {
        $resolver->define('number_pattern')
            ->default(null)
            ->allowedTypes('null', 'string')
            ->allowedValues(
                null,
                self::NUMBER_IDENTIFIER,
                self::NUMBER_INTEGER,
                self::NUMBER_PERCENT,
                self::NUMBER_AMOUNT
            );

        $resolver->define('percent_sign')
            ->default(true)
            ->allowedTypes('bool');

        $resolver->define('percent_decimals')
            ->default(2)
            ->allowedTypes('int');

        $resolver->define('percent_rounding_mode')
            ->default(\NumberFormatter::ROUND_HALFEVEN)
            ->allowedTypes('int')
            ->allowedValues(
                \NumberFormatter::ROUND_CEILING,
                \NumberFormatter::ROUND_FLOOR,
                \NumberFormatter::ROUND_DOWN,
                \NumberFormatter::ROUND_UP,
                \NumberFormatter::ROUND_HALFEVEN,
                \NumberFormatter::ROUND_HALFDOWN,
                \NumberFormatter::ROUND_HALFUP
            );
    }

    /**
     * @psalm-param OptionsType $options
     */
    private function formatArray(array $value, array $options): string
    {
        $values = \array_map(fn (mixed $item): string => $this->getDataValue($item, $options), $value);

        return \implode($options['separator'], $values);
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
        return (string) FormatUtils::formatDateTime(
            $value,
            $options['date_format'],
            $options['time_format'],
            $options['date_pattern'],
        );
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
        return match ($options['number_pattern']) {
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
        return FormatUtils::formatPercent(
            $value,
            $options['percent_sign'],
            $options['percent_decimals'],
            $options['percent_rounding_mode']
        );
    }

    /**
     * @throws TransformationFailedException if the value cannot be mapped to a string
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

        if ($value instanceof EntityInterface) {
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
    private function getDisplayValue(mixed $value, array $options, string $default): string
    {
        if (\is_callable($options['display_transformer'])) {
            return \call_user_func($options['display_transformer'], $value) ?? $default;
        }

        return $default;
    }

    private function trans(string $id): string
    {
        return $this->translator->trans($id);
    }

    /**
     * @psalm-param OptionsType $options
     */
    private function transformValue(mixed $value, array $options): mixed
    {
        if (\is_callable($options['value_transformer'])) {
            return \call_user_func($options['value_transformer'], $value) ?? $value;
        }

        return $value;
    }
}
