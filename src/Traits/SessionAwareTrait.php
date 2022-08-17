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

use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Service\Attribute\SubscribedService;

/**
 * Trait to get or set values within session.
 */
trait SessionAwareTrait
{
    /**
     * @throws ContainerExceptionInterface
     */
    #[SubscribedService]
    public function getRequestStack(): RequestStack
    {
        /** @psalm-var RequestStack $result */
        $result = $this->container->get(__CLASS__ . '::' . __FUNCTION__);

        return $result;
    }

    /**
     * Gets the session.
     */
    protected function getSession(): ?SessionInterface
    {
        try {
            return $this->getRequestStack()->getSession();
        } catch (SessionNotFoundException) {
            return null;
        }
    }

    /**
     * Gets a session attribute, as float value.
     *
     * @param string $key     the attribute name
     * @param ?float $default the default value if not found
     *
     * @return float|null the session value, if found; the default value otherwise
     */
    protected function getSessionFloat(string $key, ?float $default): ?float
    {
        return (float) $this->getSessionValue($key, $default);
    }

    /**
     * Gets a session attribute, as integer value.
     *
     * @param string $key     the attribute name
     * @param ?int   $default the default value if not found
     *
     * @return int|null the session value, if found; the default value otherwise
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
     * @param string  $key     the attribute name
     * @param ?string $default the default value if not found
     *
     * @return string|null the session value, if found; the default value otherwise
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
    protected function getSessionValue(string $key, mixed $default = null): mixed
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
    protected function removeSessionValue(string $key): mixed
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
     * @return array the removed values
     */
    protected function removeSessionValues(array $keys): array
    {
        return \array_map(fn (string $key): mixed => $this->removeSessionValue($key), $keys);
    }

    /**
     * Sets a session attribute.
     *
     * @param string $key   the attribute name
     * @param mixed  $value the attribute value or null to remove
     */
    protected function setSessionValue(string $key, mixed $value): static
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
     * Sets session attributes (values).
     *
     * @param array<string, mixed> $attributes the keys and values to save
     */
    protected function setSessionValues(array $attributes): static
    {
        /** @psalm-var mixed $value */
        foreach ($attributes as $key => $value) {
            $this->setSessionValue($key, $value);
        }

        return $this;
    }
}
