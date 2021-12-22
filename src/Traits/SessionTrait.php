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

use App\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
     * Sets the request stack.
     */
    public function setRequestStack(RequestStack $requestStack): void
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Sets the session.
     */
    public function setSession(SessionInterface $session): void
    {
        $this->session = $session;
    }

    /**
     * Gets the session.
     *
     * @return SessionInterface|null the session, if found; null otherwise
     */
    protected function getSession(): ?SessionInterface
    {
        if (null === $this->session) {
            if (null === $this->requestStack && $this instanceof AbstractController) {
                $this->requestStack = $this->getRequestStack();
            }
            if (null !== $this->requestStack) {
                $this->session = $this->requestStack->getSession();
            }
        }

        return $this->session;
    }

    /**
     * Gets a session attribute, as float value.
     *
     * @param string $key     the attribute name
     * @param float  $default the default value if not found
     *
     * @return float the session value, if found; the default value otherwise
     */
    protected function getSessionFloat(string $key, ?float $default): ?float
    {
        return (float) $this->getSessionValue($key, $default);
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
     * Removes session attributes.
     *
     * @param string[] $keys the attribute names to remove
     *
     * @return array<mixed> the removed values
     */
    protected function removeSessionValues(array $keys): array
    {
        return \array_map(function (string $key) {
            return $this->removeSessionValue($key);
        }, $keys);
    }

    /**
     * Sets this session within the given request.
     *
     * @return bool true if the session is set; false otherwise
     */
    protected function setSessionFromRequest(Request $request): bool
    {
        if ($request->hasSession()) {
            $this->session = $request->getSession();

            return true;
        }

        return false;
    }

    /**
     * Sets a session attribute.
     *
     * @param string $key   the attribute name
     * @param mixed  $value the attribute value or null to remove
     */
    protected function setSessionValue(string $key, $value): self
    {
        if ($session = $this->getSession()) {
            $sessionKey = $this->getSessionKey($key);
            if (null === $value) {
                $session->remove($key);
            } else {
                $session->set($sessionKey, $value);
            }
        }

        return $this;
    }

    /**
     * Sets session attributes.
     *
     * @param array<string, mixed> $attributes the keys and values to save
     */
    protected function setSessionValues(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $this->setSessionValue($key, $value);
        }

        return $this;
    }
}
