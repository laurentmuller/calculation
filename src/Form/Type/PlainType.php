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

use App\Interfaces\DateFormatInterface;
use App\Interfaces\EntityInterface;
use App\Utils\FormatUtils;
use App\Utils\StringUtils;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A form type that just renders the field as a span tag.
 *
 * This is useful for forms where certain fields need to be shown but not editable.
 * If the 'expanded' option is set to true, a border div is added around the span tag.
 *
 * @extends AbstractType<HiddenType>
 *
 * @phpstan-type OptionsType = array{
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
 *     display_transformer: callable(mixed):(string|null)|null,
 *     value_transformer: callable(mixed):mixed|null,
 *     ...}
 */
class PlainType extends AbstractType implements DateFormatInterface
{
    /**
     * The amount number format.
     */
    final public const string NUMBER_AMOUNT = 'price';

    /**
     * The identifier number format.
     */
    final public const string NUMBER_IDENTIFIER = 'identifier';

    /**
     * The integer number format.
     */
    final public const string NUMBER_INTEGER = 'integer';

    /**
     * The percent number format.
     */
    final public const string NUMBER_PERCENT = 'percent';

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * @phpstan-param OptionsType $options
     */
    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $data = $form->getViewData();
        $value = $this->getDataValue($data, $options);
        $display_value = $this->transform($options['display_transformer'], $data, $value);

        $view->vars = \array_replace($view->vars, [
            'value' => $value,
            'display_value' => $display_value,
            'expanded' => $options['expanded'],
            'text_class' => $options['text_class'],
            'hidden_input' => $options['hidden_input'],
        ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $this->configureDefaults($resolver);
        $this->configureNumber($resolver);
        $this->configureDate($resolver);
    }

    private function configureDate(OptionsResolver $resolver): void
    {
        $allowedValues = [null, ...\array_keys(self::DATE_FORMATS)];

        $resolver->define('date_format')
            ->default(null)
            ->allowedTypes('null', 'string')
            ->allowedValues(...$allowedValues);

        $resolver->define('time_format')
            ->default(null)
            ->allowedTypes('null', 'string')
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
     * @phpstan-param OptionsType $options
     */
    private function formatArray(array $value, array $options): string
    {
        $values = \array_map(fn (mixed $item): string => $this->getDataValue($item, $options), $value);

        return \implode($options['separator'], $values);
    }

    private function formatBool(bool $value): string
    {
        return $this->trans($value ? 'common.value_true' : 'common.value_false');
    }

    /**
     * @phpstan-param OptionsType $options
     */
    private function formatDate(DatePoint|int|null $value, array $options): string
    {
        $dateType = self::DATE_FORMATS[$options['date_format']] ?? null;
        $timeType = self::DATE_FORMATS[$options['time_format']] ?? null;
        $pattern = $options['date_pattern'];

        return (string) FormatUtils::formatDateTime($value, $dateType, $timeType, $pattern);
    }

    /**
     * @phpstan-param OptionsType $options
     */
    private function formatEmpty(mixed $value, array $options): string
    {
        if (\is_callable($options['empty_value'])) {
            return \call_user_func($options['empty_value'], $value);
        }

        return $this->trans($options['empty_value'] ?? 'common.value_null');
    }

    /**
     * @phpstan-param OptionsType $options
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
     * @phpstan-param OptionsType $options
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
     * @phpstan-param OptionsType $options
     *
     * @throws TransformationFailedException if the value cannot be mapped to a string
     */
    private function getDataValue(mixed $value, array $options): string
    {
        $value = $this->transform($options['value_transformer'], $value, $value);

        return match (true) {
            $value instanceof EntityInterface => $value->getDisplay(),
            $value instanceof DatePoint => $this->formatDate($value, $options),
            null === $value || '' === $value => $this->formatEmpty($value, $options),
            \is_bool($value) => $this->formatBool($value),
            \is_array($value) => $this->formatArray($value, $options),
            \is_numeric($value) => $this->formatNumber($value, $options),
            \is_scalar($value) || $value instanceof \Stringable => (string) $value,
            default => throw new TransformationFailedException(\sprintf('Unable to map instance of "%s" to string.', StringUtils::getDebugType($value)))
        };
    }

    private function trans(string $id): string
    {
        return $this->translator->trans($id);
    }

    /**
     * @template TValue
     *
     * @param ?callable(mixed): ?TValue $callable
     * @param TValue                    $default
     */
    private function transform(?callable $callable, mixed $value, mixed $default): mixed
    {
        if (\is_callable($callable)) {
            return \call_user_func($callable, $value) ?? $default;
        }

        return $default;
    }
}
