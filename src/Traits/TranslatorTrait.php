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
     * @param string $id     the message id (may also be an object that can be cast to string)
     * @param string $domain the domain for the message or null to use the default
     * @param string $locale the locale or null to use the default
     *
     * @return bool true if the message has a translation, false otherwise
     */
    public function isTransDefined(string $id, ?string $domain = null, ?string $locale = null): bool
    {
        if ($translator = $this->doGetTranslator()) {
            if ($translator instanceof TranslatorBagInterface) {
                /** @var \Symfony\Component\Translation\MessageCatalogueInterface $catalogue */
                $catalogue = $translator->getCatalogue($locale);

                return $catalogue->defines($id, $domain ?? 'messages');
            }
        }

        return $id !== $this->trans($id, [], $domain, $locale);
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
     *
     * @throws \InvalidArgumentException If the locale contains invalid characters
     */
    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        if ($translator = $this->doGetTranslator()) {
            return $translator->trans($id, $parameters, $domain, $locale);
        }

        return $id;
    }

    /**
     * Gets the translator.
     *
     * @return TranslatorInterface|null the translator if found; null otherwise
     */
    protected function doGetTranslator(): ?TranslatorInterface
    {
        if (!$this->translator && \method_exists($this, 'getTranslator')) {
            return $this->translator = $this->getTranslator();
        }

        return $this->translator;
    }
}
