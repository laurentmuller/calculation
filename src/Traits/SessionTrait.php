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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Trait for session functions.
 *
 * @author Laurent Muller
 */
trait SessionTrait
{
    /**
     * The request stack used to get session.
     */
    protected ?RequestStack $requestStack = null;

    /**
     * The session instance.
     */
    protected ?SessionInterface $session = null;

    /**
     * Gets the session.
     *
     * @return SessionInterface|null the session, if found; null otherwise
     */
    protected function getSession(): ?SessionInterface
    {
        if (null === $this->session) {
            if (null === $this->requestStack && $this instanceof AbstractController) {
                /** @var RequestStack $requestStack */
                $requestStack = $this->get('request_stack');
                $this->requestStack = $requestStack;
            }
            if (null !== $this->requestStack) {
                $this->session = $this->requestStack->getSession();
            }
        }

        return $this->session;
    }

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
        if ($session = $this->getSession()) {
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
        if ($session = $this->getSession()) {
            $sessionKey = $this->getSessionKey($key);

            return $session->has($sessionKey);
        }

        return false;
    }

    /**
     * Gets a session attribute, as boolean value.
     *
     * @param string $key     the attribute name
     * @param bool   $default the default value if not found
     *
     * @return bool the session value, if found; the default value otherwise
     */
    protected function isSessionBool(string $key, bool $default = false): bool
    {
        return (bool) $this->getSessionValue($key, $default);
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
        if ($session = $this->getSession()) {
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
        if ($session = $this->getSession()) {
            $sessionKey = $this->getSessionKey($key);
            $session->set($sessionKey, $value);
        }

        return $this;
    }
}
