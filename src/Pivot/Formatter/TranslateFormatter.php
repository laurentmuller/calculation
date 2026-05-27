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

namespace App\Pivot\Formatter;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Translates the given value.
 */
readonly class TranslateFormatter implements FormatterInterface
{
    /**
     * @param TranslatorInterface $translator the translator
     * @param string              $key        the translation key (the message id)
     * @param string              $parameter  the translation parameter name
     * @param ?string             $domain     the domain for the message or null to use the default
     */
    public function __construct(
        private TranslatorInterface $translator,
        private string $key,
        private string $parameter,
        private ?string $domain = null
    ) {
    }

    #[\Override]
    public function format(int|float|string $value): string
    {
        return $this->translator->trans($this->key, [$this->parameter => $value], $this->domain);
    }
}
