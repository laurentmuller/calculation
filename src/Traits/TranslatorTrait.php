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

namespace App\Traits;

use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Trait for translations.
 */
trait TranslatorTrait
{
    /**
     * Gets the translator.
     */
    abstract public function getTranslator(): TranslatorInterface;

    /**
     * Checks if a message has a translation (it does not take into account the fallback mechanism).
     *
     * @param string  $id     the message identifier (may also be an object that can be cast to string)
     * @param ?string $domain the domain for the message or null to use the default ('messages')
     * @param ?string $locale the locale or null to use the default
     *
     * @return bool true if the message has a translation, false otherwise
     */
    public function isTransDefined(string $id, ?string $domain = null, ?string $locale = null): bool
    {
        $translator = $this->getTranslator();
        if ($translator instanceof TranslatorBagInterface) {
            $catalogue = $translator->getCatalogue($locale);

            return $catalogue->defines($id, $domain ?? 'messages');
        }

        return $id !== $this->trans($id, [], $domain, $locale);
    }

    /**
     * Translates the given message.
     *
     * @param string|\Stringable|TranslatableInterface $id         the message identifier; may also be an object that
     *                                                             can be cast to string
     * @param array                                    $parameters an array of parameters for the message
     * @param ?string                                  $domain     the domain for the message or null to use the default
     * @param ?string                                  $locale     the locale or null to use the default
     *
     * @return string the translated string
     */
    public function trans(
        string|\Stringable|TranslatableInterface $id,
        array $parameters = [],
        ?string $domain = null,
        ?string $locale = null
    ): string {
        $translator = $this->getTranslator();
        if ($id instanceof TranslatableInterface) {
            return $id->trans($translator, $locale);
        }

        return $translator->trans((string) $id, $parameters, $domain, $locale);
    }
}
