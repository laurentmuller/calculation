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
use Twig\Attribute\AsTwigFilter;

/**
 * Service to get Emoji country flags.
 */
class CountryFlagService
{
    private const REGIONAL_OFFSET = 0x01F1A5;

    /**
     * Gets choice values used for the country type form.
     *
     * @param ?string $locale   the locale used to translate country names or null to use default
     * @param bool    $flagOnly true to return a flag only; false to return flags and country names
     *
     * @return array<string, string> an array where the key is the flag and the country name (if applicable)
     *                               and value is the country code
     */
    public function getChoices(?string $locale = null, bool $flagOnly = false): array
    {
        $names = Countries::getNames($locale);
        foreach ($names as $code => &$name) {
            $name = $this->getChoiceEntry($code, $name, $flagOnly);
        }

        return \array_flip($names);
    }

    /**
     * Gets the default country code.
     */
    public static function getDefaultCode(): string
    {
        /** @phpstan-var string */
        return \Locale::getRegion(\Locale::getDefault());
    }

    /**
     * Gets the Emoji flag for the given country code.
     *
     * @param string $alpha2Code the country code (ISO 3166-1 alpha-2) to get the Emoji flag for
     * @param bool   $validate   true to validate the given country code
     *
     * @throws \InvalidArgumentException if the <code>$validate</code> parameter is <code>true</code>,
     *                                   and the given country code does not exist
     *
     * @see Countries::exists()
     */
    #[AsTwigFilter(name: 'flag_emoji')]
    public function getFlag(string $alpha2Code, bool $validate = true): string
    {
        if (Countries::exists($alpha2Code)) {
            return $this->getEmojiChar($alpha2Code[0]) . $this->getEmojiChar($alpha2Code[1]);
        }
        if ($validate) {
            throw new \InvalidArgumentException("Invalid country code: '$alpha2Code'.");
        }

        return '';
    }

    private function getChoiceEntry(string $code, string $name, bool $flagOnly): string
    {
        $flag = $this->getFlag($code, false);

        return $flagOnly ? $flag : \sprintf('%s %s', $name, $flag);
    }

    private function getEmojiChar(string $chr): string
    {
        /** @phpstan-var int $order */
        $order = \mb_ord($chr, 'UTF-8');

        /** @phpstan-var string */
        return \mb_chr(self::REGIONAL_OFFSET + $order, 'UTF-8');
    }
}
