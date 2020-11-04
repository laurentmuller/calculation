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

namespace App\Traits;

use App\Util\FormatUtils;
use NumberFormatter;

/**
 * Trait to format numbers.
 *
 * @author Laurent Muller
 *
 * @see \NumberFormatter
 */
trait NumberFormatterTrait
{
    /**
     * Filter to format a number with 2 decimals (Ex: 12.5 -> 12.50).
     *
     * @param float|int $number the value to format
     */
    public function localeAmount($number): string
    {
        static $priceFormatter;
        if (!$priceFormatter) {
            $priceFormatter = $this->getNumberFormatter(NumberFormatter::DECIMAL, 2);
        }

        return $priceFormatter->format((float) $number);
    }

    /**
     * Filter to format an integer identifier with 0 left padding  (Ex: 123 -> 000123).
     *
     * @param int $number the value to format
     */
    public function localeId($number): string
    {
        return \sprintf('%06d', (int) $number);
    }

    /**
     * Filter to format a number with 0 decimals (Ex: 12.2 -> 12).
     *
     * @param float|int $number the value to format
     */
    public function localeInt($number): string
    {
        static $intFormatter;
        if (!$intFormatter) {
            $intFormatter = $this->getNumberFormatter(NumberFormatter::DECIMAL, 0);
        }

        return $intFormatter->format((float) $number);
    }

    /**
     * Filter to format a number as percent.
     *
     * @param float $number      the value to format
     * @param bool  $includeSign true to include the percent sign
     * @param int   $decimals    the number of decimals
     */
    public function localePercent($number, bool $includeSign = true, int $decimals = 0): string
    {
        $formatter = $this->getNumberFormatter(NumberFormatter::PERCENT, $decimals);
        if (!$includeSign) {
            $formatter->setSymbol(NumberFormatter::PERCENT_SYMBOL, '');
        }

        return $formatter->format((float) $number);
    }

    /**
     * Gets the default decimal separator.
     *
     * @return string the decimal separator
     */
    protected function getDefaultDecimal(): string
    {
        return FormatUtils::getDecimal();
    }

    /**
     * Gets the default grouping separator.
     *
     * @return string the grouping separator
     */
    protected function getDefaultGrouping(): string
    {
        return FormatUtils::getGrouping();
    }

    /**
     * Gets a number formatter.
     *
     * @param int $style  the style of the formatting
     * @param int $digits the number of fraction digits
     */
    protected function getNumberFormatter(int $style, int $digits): NumberFormatter
    {
        // create
        $locale = \Locale::getDefault();
        $formatter = NumberFormatter::create($locale, $style);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $digits);
        $formatter->setSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL, $this->getDefaultGrouping());
        $formatter->setSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL, $this->getDefaultDecimal());

        return $formatter;
    }
}
