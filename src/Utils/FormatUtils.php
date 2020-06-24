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

namespace App\Utils;

use App\Form\Type\SeparatorType;

/**
 * Utility class for default formats.
 *
 * @author Laurent Muller
 */
final class FormatUtils
{
    /**
     * Gets the default date type format.
     *
     * @return int type of date formatting, one of the format type constants
     */
    public static function getDateType(): int
    {
        return \IntlDateFormatter::SHORT;
    }

    /**
     * Gets the default decimal separator for the current locale.
     *
     * @return string the decimal separator
     */
    public static function getDecimal(): string
    {
        $locale = \Locale::getDefault();

        // special case for Swiss French
        if ('fr_CH' === $locale) {
            return SeparatorType::PERIOD_CHAR;
        }

        $formatter = \NumberFormatter::create($locale, \NumberFormatter::DECIMAL);

        return $formatter->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
    }

    /**
     * Gets the default grouping separator for the current locale.
     *
     * @return string the grouping separator
     */
    public static function getGrouping(): string
    {
        $locale = \Locale::getDefault();

        // special case for Swiss French
        if ('fr_CH' === $locale) {
            return SeparatorType::QUOTE_CHAR;
        }

        $formatter = \NumberFormatter::create($locale, \NumberFormatter::DECIMAL);
        $symbol = $formatter->getSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL);

        // special case when space is in 2 characters
        if (2 === \strlen($symbol) && 194 === \ord($symbol[0]) && 160 === \ord($symbol[1])) {
            $symbol = SeparatorType::SPACE_CHAR;
        }

        return $symbol;
    }

    /**
     * Gets the default time type format.
     *
     * @return int type of time formatting, one of the format type constants
     */
    public static function getTimeType(): int
    {
        return \IntlDateFormatter::SHORT;
    }
}
