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

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Trait for session functions.
 *
 * @author Laurent Muller
 */
trait SessionTrait
{
    /**
     * The session instance.
     *
     * @var SessionInterface
     */
    protected $session;

    /**
     * Gets a session attribute, as integer value.
     *
     * @param string $key     the attribute name
     * @param int    $default the default value if not found
     *
     * @return int the session value, if found; the default value otherwise
     */
    protected function getSessionInt(string $key, ?int $default): ?int
    {
        return (int) $this->getSessionValue($key, $default);
    }

    /**
     * Gets the attribute name used to manipulate session attributes.
     *
     * The default implementation returns the key argument. Class can override
     * to use, for example, a prefix or a suffix.
     *
     * @param string $key the attribute name
     */
    protected function getSessionKey(string $key): string
    {
        return $key;
    }

    /**
     * Gets a session attribute, as string value.
     *
     * @param string $key     the attribute name
     * @param string $default the default value if not found
     *
     * @return string the session value, if found; the default value otherwise
     */
    protected function getSessionString(string $key, ?string $default = null): ?string
    {
        return (string) $this->getSessionValue($key, $default);
    }

    /**
     * Gets a session attribute.
     *
     * @param string $key     the attribute name
     * @param mixed  $default the default value if not found
     *
     * @return mixed the session value, if found; the default value otherwise
     */
    protected function getSessionValue(string $key, $default = null)
    {
        if ($session = $this->doGetSession()) {
            $sessionKey = $this->getSessionKey($key);

            return $session->get($sessionKey, $default);
        }

        return $default;
    }

    /**
     * Checks if an attribute is defined.
     *
     * @param string $key the attribute name
     *
     * @return bool true if the attribute is defined, false otherwise
     */
    protected function hasSessionValue(string $key): bool
    {
        if ($session = $this->doGetSession()) {
            $sessionKey = $this->getSessionKey($key);

            return $session->has($sessionKey);
        }

        return false;
    }

    /**
     * Removes a session attribute.
     *
     * @param string $key the attribute name
     *
     * @return mixed the removed value or null when attribute does not exist
     */
    protected function removeSessionValue(string $key)
    {
        if ($session = $this->doGetSession()) {
            $sessionKey = $this->getSessionKey($key);

            return $session->remove($sessionKey);
        }

        return null;
    }

    /**
     * Sets a session attribute.
     *
     * @param string $key   the attribute name
     * @param mixed  $value the value to save
     */
    protected function setSessionValue(string $key, $value): self
    {
        if ($session = $this->doGetSession()) {
            $sessionKey = $this->getSessionKey($key);
            $session->set($sessionKey, $value);
        }

        return $this;
    }

    /**
     * Gets the session.
     *
     * @return SessionInterface|null the session if found; null otherwise
     */
    private function doGetSession(): ?SessionInterface
    {
        if (!$this->session && \method_exists($this, 'getSession')) {
            return $this->session = $this->getSession();
        }

        return $this->session;
    }
}
