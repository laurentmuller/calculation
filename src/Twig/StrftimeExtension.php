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

namespace App\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension to use the <a href="http://www.php.net/manual/en/function.strftime.php">strftime</a> function.
 *
 * @author Laurent Muller
 */
final class StrftimeExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('strftime', [$this, 'strftimeFilter'], ['needs_environment' => true]),
        ];
    }

    /**
     * Formats the given date.
     *
     * @param Environment                              $env      the Twig environment
     * @param \DateTime|\DateTimeInterface|string|null $date     the date to format
     * @param string                                   $format   the format to use. The default value use the date and the time representation based on locale.
     * @param \DateTimeZone|false|string|null          $timezone the target timezone, null to use the default, false to leave unchanged
     *
     * @return string a string formatted according format using the given date
     *
     * @see http://www.php.net/manual/en/function.strftime.php
     */
    public function strftimeFilter(Environment $env, $date, $format = '%c', $timezone = null): string
    {
        // convert
        $date = twig_date_converter($env, $date, $timezone);

        // locale
        $locale = \Locale::getDefault();
        if (false === \setlocale(\LC_TIME, $locale)) {
            \setlocale(\LC_TIME, \Locale::getPrimaryLanguage($locale));
        }

        // windows?
        if ('WIN' === \strtoupper(\substr(\PHP_OS, 0, 3))) {
            $format = \preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $format);
        }

        // format with first character uppercase
        $string = \ucfirst(\strftime($format, $date->getTimestamp()));

        // encode
        return \utf8_encode($string);
    }
}
