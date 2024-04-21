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

namespace App\Pdf\Html;

/**
 * Lists type enumeration.
 *
 * @see HtmlOlChunk
 */
enum HtmlListType: string
{
    /*
     * Numbered list with lowercase letters.
     */
    case LETTER_LOWER = 'a';
    /*
     * Numbered list with uppercase letters.
     */
    case LETTER_UPPER = 'A';
    /*
     * Numbered list with numbers (default).
     */
    case NUMBER = '1';
    /*
     * Numbered list with lowercase roman numbers.
     *
     * <b>N.B.:</b> Allowed value must be in range form 1 to 3999 (inclusive).
     */
    case ROMAN_LOWER = 'i';
    /*
     * Numbered list with uppercase roman numbers.
     *
     * <b>N.B.:</b> Allowed value must be in range form 1 to 3999 (inclusive).
     */
    case ROMAN_UPPER = 'I';

    /**
     * Gets the bullet text for the given number.
     *
     * @param int    $number the list item index (one index-based)
     * @param string $suffix the suffix to append
     *
     * @return string the bullet text or an empty string if the number is not positive
     */
    public function getBulletText(int $number, string $suffix = '.'): string
    {
        if ($number <= 0) {
            return '';
        }

        return match ($this) {
            HtmlListType::LETTER_LOWER => $this->toLetterLower($number, $suffix) ,
            HtmlListType::LETTER_UPPER => $this->toLetterUpper($number, $suffix),
            HtmlListType::ROMAN_LOWER => $this->toRomanLower($number, $suffix),
            HtmlListType::ROMAN_UPPER => $this->toRomanUpper($number, $suffix),
            HtmlListType::NUMBER => $this->toNumber($number, $suffix),
        };
    }

    /**
     * Converts the value to lower letters.
     *
     * @param int    $number the value to convert
     * @param string $suffix the suffix to append
     *
     * @return string the letters
     */
    private function toLetterLower(int $number, string $suffix): string
    {
        return \strtolower($this->toLetterUpper($number, $suffix));
    }

    /**
     * Converts the value to upper letters.
     *
     * @param int    $number the value to convert
     * @param string $suffix the suffix to append
     *
     * @return string the letters
     */
    private function toLetterUpper(int $number, string $suffix): string
    {
        if ($number <= 26) {
            return \chr(64 + $number) . $suffix;
        }

        return \str_repeat('A', \intdiv($number, 26)) . self::toLetterUpper($number % 26, $suffix);
    }

    /**
     * Converts the value to string.
     *
     * @param int    $number the value to convert
     * @param string $suffix the suffix to append
     *
     * @return string the string
     */
    private function toNumber(int $number, string $suffix): string
    {
        return \sprintf('%d%s', $number, $suffix);
    }

    /**
     * Converts the value to a lower roman number.
     *
     * <b>N.B.:</b> Returns an empty string if the number is greater than 3999.
     *
     * @param int    $number the value to convert
     * @param string $suffix the suffix to append
     *
     * @return string the roman number
     */
    private function toRomanLower(int $number, string $suffix): string
    {
        return \strtolower($this->toRomanUpper($number, $suffix));
    }

    /**
     * Converts the value to an upper roman number.
     *
     * <b>N.B.:</b> Returns an empty string if the number is greater than 3999.
     *
     * @param int    $number the value to convert
     * @param string $suffix the suffix to append
     *
     * @return string the roman number
     */
    private function toRomanUpper(int $number, string $suffix): string
    {
        if ($number > 3999) {
            return '';
        }
        $formatter = new \NumberFormatter('@numbers=roman', \NumberFormatter::DEFAULT_STYLE);

        return $formatter->format($number) . $suffix;
    }
}
