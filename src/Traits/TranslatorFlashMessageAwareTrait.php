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

use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * A combination of the translator trait and the flash message trait to add translated flash messages.
 */
trait TranslatorFlashMessageAwareTrait
{
    use FlashMessageAwareTrait;
    use TranslatorAwareTrait;

    /**
     * Add a translated error message to the session flash bag.
     *
     * @param string|\Stringable|TranslatableInterface $id         the message identifier
     *                                                             (may also be an object that can be cast to string)
     * @param array                                    $parameters an array of parameters for the message
     * @param ?string                                  $domain     the domain or null to use the default
     * @param ?string                                  $locale     the locale or null to use the default
     *
     * @return string the translated message
     */
    protected function errorTrans(
        string|\Stringable|TranslatableInterface $id,
        array $parameters = [],
        ?string $domain = null,
        ?string $locale = null
    ): string {
        $message = $this->trans($id, $parameters, $domain, $locale);
        $this->error($message);

        return $message;
    }

    /**
     * Add a translated information message to the session flash bag.
     *
     * @param string|\Stringable|TranslatableInterface $id         the message identifier
     *                                                             (may also be an object that can be cast to string)
     * @param array                                    $parameters an array of parameters for the message
     * @param ?string                                  $domain     the domain or null to use the default
     * @param ?string                                  $locale     the locale or null to use the default
     *
     * @return string the translated message
     */
    protected function infoTrans(
        string|\Stringable|TranslatableInterface $id,
        array $parameters = [],
        ?string $domain = null,
        ?string $locale = null
    ): string {
        $message = $this->trans($id, $parameters, $domain, $locale);
        $this->info($message);

        return $message;
    }

    /**
     * Add a translated success message to the session flash bag.
     *
     * @param string|\Stringable|TranslatableInterface $id         the message identifier
     *                                                             (may also be an object that can be cast to string)
     * @param array                                    $parameters an array of parameters for the message
     * @param ?string                                  $domain     the domain or null to use the default
     * @param ?string                                  $locale     the locale or null to use the default
     *
     * @return string the translated message
     */
    protected function successTrans(
        string|\Stringable|TranslatableInterface $id,
        array $parameters = [],
        ?string $domain = null,
        ?string $locale = null
    ): string {
        $message = $this->trans($id, $parameters, $domain, $locale);
        $this->success($message);

        return $message;
    }

    /**
     * Add a translated warning message to the session flash bag.
     *
     * @param string|\Stringable|TranslatableInterface $id         the message identifier
     *                                                             (may also be an object that can be cast to string)
     * @param array                                    $parameters an array of parameters for the message
     * @param ?string                                  $domain     the domain or null to use the default
     * @param ?string                                  $locale     the locale or null to use the default
     *
     * @return string the translated message
     */
    protected function warningTrans(
        string|\Stringable|TranslatableInterface $id,
        array $parameters = [],
        ?string $domain = null,
        ?string $locale = null
    ): string {
        $message = $this->trans($id, $parameters, $domain, $locale);
        $this->warning($message);

        return $message;
    }
}
