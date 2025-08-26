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

namespace App\Twig;

use App\Traits\TranslatorTrait;
use App\Utils\FormatUtils;
use App\Utils\StringUtils;
use Symfony\Component\Clock\DatePoint;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Attribute\AsTwigFilter;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;

/**
 * Twig extension to format dates, numbers or boolean values.
 */
final class FormatExtension
{
    use TranslatorTrait;

    private const DATE_FORMATS = [
        'none' => \IntlDateFormatter::NONE,
        'short' => \IntlDateFormatter::SHORT,
        'medium' => \IntlDateFormatter::MEDIUM,
        'long' => \IntlDateFormatter::LONG,
        'full' => \IntlDateFormatter::FULL,
    ];

    private ?CoreExtension $extension = null;

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * Filter to format a boolean value.
     *
     * @param bool   $value      the value to format
     * @param string $trueLabel  the translatable text to use when the value is true
     * @param string $falseLabel the translatable text to use when the value is false
     */
    #[AsTwigFilter(name: 'boolean')]
    public function formatBoolean(
        bool $value,
        string $trueLabel = 'common.value_true',
        string $falseLabel = 'common.value_false'
    ): string {
        return $this->trans($value ? $trueLabel : $falseLabel);
    }

    /**
     * Formats a date for the current locale; ignoring the time part.
     *
     * @param Environment           $env        the Twig environment
     * @param DatePoint|string|null $date       the date
     * @param string|null           $dateFormat the date format
     * @param string|null           $pattern    the optional pattern to use when formatting
     *
     * @return string the formatted date
     *
     * @throws RuntimeError if the date format is invalid
     */
    #[AsTwigFilter(name: 'locale_date', needsEnvironment: true)]
    public function formatDate(
        Environment $env,
        DatePoint|string|null $date,
        ?string $dateFormat = null,
        ?string $pattern = null
    ): string {
        return $this->formatDateTime($env, $date, $dateFormat, 'none', $pattern);
    }

    /**
     * Formats a date and time for the current locale.
     *
     * @param Environment           $env        the Twig environment
     * @param DatePoint|string|null $date       the date
     * @param string|null           $dateFormat the date format
     * @param string|null           $timeFormat the time format
     * @param string|null           $pattern    the optional pattern to use when formatting
     *
     * @return string the formatted date
     *
     * @throws RuntimeError if the date format or the time format is invalid
     */
    #[AsTwigFilter(name: 'locale_datetime', needsEnvironment: true)]
    public function formatDateTime(
        Environment $env,
        DatePoint|string|null $date,
        ?string $dateFormat = null,
        ?string $timeFormat = null,
        ?string $pattern = null
    ): string {
        // check types
        $dateType = $this->translateFormat($dateFormat);
        $timeType = $this->translateFormat($timeFormat);
        if (\IntlDateFormatter::NONE === $dateType && \IntlDateFormatter::NONE === $timeType
                && !StringUtils::isString($pattern)) {
            return '';
        }

        $date = $this->convertDate($env, $date);

        return FormatUtils::formatDateTime($date, $dateType, $timeType, $pattern);
    }

    /**
     * Formats a time for the current locale; ignoring the date part.
     *
     * @param Environment           $env        the Twig environment
     * @param DatePoint|string|null $date       the date
     * @param string|null           $timeFormat the time format
     * @param string|null           $pattern    the optional pattern to use when formatting
     *
     * @return string the formatted date
     *
     * @throws RuntimeError if the time format is invalid
     */
    #[AsTwigFilter(name: 'locale_time', needsEnvironment: true)]
    public function formatTime(
        Environment $env,
        DatePoint|string|null $date,
        ?string $timeFormat = null,
        ?string $pattern = null
    ): string {
        return $this->formatDateTime($env, $date, 'none', $timeFormat, $pattern);
    }

    #[\Override]
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    private function convertDate(Environment $env, \DateTimeInterface|string|null $date): DatePoint
    {
        $date = $this->getCoreExtension($env)->convertDate($date);

        return DatePoint::createFromInterface($date);
    }

    private function getCoreExtension(Environment $env): CoreExtension
    {
        return $this->extension ??= $env->getExtension(CoreExtension::class);
    }

    /**
     * Check the time format.
     *
     * @return int<-1,3>|null
     *
     * @throws RuntimeError
     */
    private function translateFormat(?string $format = null): ?int
    {
        if (null === $format || '' === $format) {
            return null;
        }
        if (!isset(self::DATE_FORMATS[$format])) {
            $formats = \implode('", "', \array_keys(self::DATE_FORMATS));
            $message = \sprintf('The date/time type "%s" does not exist. Allowed values are: "%s".', $format, $formats);
            throw new RuntimeError($message);
        }

        return self::DATE_FORMATS[$format];
    }
}
