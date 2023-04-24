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

namespace App\Service;

use Symfony\Component\Intl\Countries;

/**
 * Service to get Emoji country flags.
 */
class CountryFlagService
{
    private const REGIONAL_OFFSET = 0x01F1A5;

    /**
     * Gets choice values used by country type form.
     *
     * @param ?string $locale   the locale used to translate country name or null to use default
     * @param bool    $flagOnly true to return flag only; false to return flag and country name
     *
     * @return array<string, string> an array where key is the flag and the country name (if applicable) and value is the country code
     */
    public function getChoices(string $locale = null, bool $flagOnly = false): array
    {
        $choices = [];
        $names = Countries::getNames($locale);
        if ($flagOnly) {
            foreach (\array_keys($names) as $code) {
                $choices[self::getFlag($code, false)] = $code;
            }
        } else {
            foreach ($names as $code => $name) {
                $choices[\sprintf('%s %s', self::getFlag($code, false), $name)] = $code;
            }
        }

        return $choices;
    }

    /**
     * Gets the default country code.
     */
    public static function getDefaultCode(): string
    {
        return \Locale::getRegion(\Locale::getDefault());
    }

    /**
     * Gets the Emoji flag for the given country code.
     *
     * @param string $code     the country code to get Emoji flag for
     * @param bool   $validate true to validate the given country code
     *
     * @throws \InvalidArgumentException if the validate parameter is true and the given country code does not exist
     *
     * @see Countries::exists()
     */
    public static function getFlag(string $code, bool $validate = true): string
    {
        if ($validate && !Countries::exists($code)) {
            throw new \InvalidArgumentException("Invalid country code: '$code'.");
        }

        return self::getEmojiChar($code[0]) . self::getEmojiChar($code[1]);
    }

    private static function getEmojiChar(string $chr): string
    {
        return \mb_chr(self::REGIONAL_OFFSET + \mb_ord($chr, 'UTF-8'), 'UTF-8');
    }
}
