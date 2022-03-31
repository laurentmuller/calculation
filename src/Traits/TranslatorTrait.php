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

namespace App\Traits;

use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Trait for translations.
 *
 * @author Laurent Muller
 */
trait TranslatorTrait
{
    /**
     * The translator instance.
     */
    protected ?TranslatorInterface $translator = null;

    /**
     * Checks if a message has a translation (it does not take into account the fallback mechanism).
     *
     * @param string      $id     the message id (may also be an object that can be cast to string)
     * @param string|null $domain the domain for the message or null to use the default
     * @param string|null $locale the locale or null to use the default
     *
     * @return bool true if the message has a translation, false otherwise
     */
    public function isTransDefined(string $id, ?string $domain = null, ?string $locale = null): bool
    {
        if ($this->translator instanceof TranslatorBagInterface) {
            $catalogue = $this->translator->getCatalogue($locale);

            return $catalogue->defines($id, $domain ?? 'messages');
        }

        return $id !== $this->trans($id, [], $domain, $locale);
    }

    /**
     * Sets the translator.
     */
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    /**
     * Translates the given message.
     *
     * @param string      $id         the message id (may also be an object that can be cast to string)
     * @param array       $parameters an array of parameters for the message
     * @param string|null $domain     the domain for the message or null to use the default
     * @param string|null $locale     the locale or null to use the default
     *
     * @return string the translated string
     */
    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        if ($this->translator instanceof TranslatorInterface) {
            return $this->translator->trans($id, $parameters, $domain, $locale);
        }

        return $id;
    }
}
