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

/**
 * A combination of the translator trait and the flash message trait to add tanslated flash messages.
 *
 * @author Laurent Muller
 */
trait TranslatorFlashMessageTrait
{
    use FlashMessageTrait;
    use TranslatorTrait;

    /**
     * Add a translated error message to the session flash bag.
     *
     * @param string      $id         the message id (may also be an object that can be cast to string)
     * @param array       $parameters an array of parameters for the message
     * @param string|null $domain     the domain for the message or null to use the default
     * @param string|null $locale     the locale or null to use the default
     *
     * @return string the translated message
     *
     * @throws \InvalidArgumentException If the locale contains invalid characters
     */
    protected function errorTrans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        $message = $this->trans($id, $parameters, $domain, $locale);
        $this->error($message);

        return $message;
    }

    /**
     * Add a translated information message to the session flash bag.
     *
     * @param string      $id         the message id (may also be an object that can be cast to string)
     * @param array       $parameters an array of parameters for the message
     * @param string|null $domain     the domain for the message or null to use the default
     * @param string|null $locale     the locale or null to use the default
     *
     * @return string the translated message
     *
     * @throws \InvalidArgumentException If the locale contains invalid characters
     */
    protected function infoTrans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        $message = $this->trans($id, $parameters, $domain, $locale);
        $this->info($message);

        return $message;
    }

    /**
     * Add a translated success message to the session flash bag.
     *
     * @param string      $id         the message id (may also be an object that can be cast to string)
     * @param array       $parameters an array of parameters for the message
     * @param string|null $domain     the domain for the message or null to use the default
     * @param string|null $locale     the locale or null to use the default
     *
     * @return string the translated message
     *
     * @throws \InvalidArgumentException If the locale contains invalid characters
     */
    protected function succesTrans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        $message = $this->trans($id, $parameters, $domain, $locale);
        $this->succes($message);

        return $message;
    }

    /**
     * Add a translated warning message to the session flash bag.
     *
     * @param string      $id         the message id (may also be an object that can be cast to string)
     * @param array       $parameters an array of parameters for the message
     * @param string|null $domain     the domain for the message or null to use the default
     * @param string|null $locale     the locale or null to use the default
     *
     * @return string the translated message
     *
     * @throws \InvalidArgumentException If the locale contains invalid characters
     */
    protected function warningTrans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        $message = $this->trans($id, $parameters, $domain, $locale);
        $this->warning($message);

        return $message;
    }
}
