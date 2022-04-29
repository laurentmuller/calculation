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
enum HtmlListType : string
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
     */
    case ROMAN_LOWER = 'i';
    /*
     * Numbered list with uppercase roman numbers.
     */
    case ROMAN_UPPER = 'I';
    /**
     * Gets the bullet text for the given number.
     *
     * @param int    $number the list item index (1 based)
     * @param string $suffix the suffix to append
     *
     * @return string the bullet text
     */
    public function getBulletText(int $number, string $suffix = '.'): string
    {
        return match ($this) {
            HtmlListType::LETTER_LOWER => \strtolower(self::toLetter($number, $suffix)) ,
            HtmlListType::LETTER_UPPER => self::toLetter($number, $suffix),
            HtmlListType::ROMAN_LOWER => \strtolower(self::toRoman($number, $suffix)),
            HtmlListType::ROMAN_UPPER => self::toRoman($number, $suffix),
            HtmlListType::NUMBER => $number <= 0 ? '' : $number . $suffix,
        };
    }

    /**
     * Converts the value to upper letters.
     *
     * @param int    $number the value to convert
     * @param string $suffix the suffix to append
     *
     * @return string the letters
     */
    public static function toLetter(int $number, string $suffix = ''): string
    {
        $text = '';
        if ($number <= 0) {
            return $text;
        }
        while ($number > 26) {
            $text .= 'A';
            $number -= 26;
        }
        // 64 = 'A'
        return $text . \chr(64 + $number) . $suffix;
    }

    /**
     * Converts the value to an upper roman number.
     *
     * <b>N.B.:</b> If value is smaller than or equal to 0 or if value is greater than 4999, an empty string ('') is returned.
     *
     * @param int    $number the value to convert
     * @param string $suffix the suffix to append
     *
     * @return string the roman number
     */
    public static function toRoman(int $number, string $suffix = ''): string
    {
        // lookup array used to traverse the number
        /** @psalm-var array<string, int> $lookup */
        static $lookup = [
            'M' => 1000,
            'CM' => 900,
            'D' => 500,
            'CD' => 400,
            'C' => 100,
            'XC' => 90,
            'L' => 50,
            'XL' => 40,
            'X' => 10,
            'IX' => 9,
            'V' => 5,
            'IV' => 4,
            'I' => 1,
        ];

        $result = '';
        if ($number <= 0 || $number > 4999) {
            return $result;
        }
        foreach ($lookup as $roman => $value) {
            $multiplier = (int) ($number / $value);
            $result .= \str_repeat($roman, $multiplier);
            $number %= $value;
        }

        return $result . $suffix;
    }
}
