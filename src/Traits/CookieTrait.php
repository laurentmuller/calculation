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

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait to manage cookies.
 */
trait CookieTrait
{
    /**
     * Clears a cookie in the browser.
     */
    protected function clearCookie(Response $response, string $key, string $prefix = ''): void
    {
        $name = $this->getCookieName($key, $prefix);
        $response->headers->clearCookie($name);
    }

    /**
     * Gets the cookie name.
     */
    protected function getCookieName(string $key, string $prefix = ''): string
    {
        return '' === $prefix ? \strtoupper($key) : \strtoupper($prefix . '_' . $key);
    }

    /**
     * Sets a cookie in the browser.
     */
    protected function setCookie(Response $response, string $key, mixed $value, string $prefix = '', string $modify = '+1 year'): void
    {
        $name = $this->getCookieName($key, $prefix);
        $expire = (new \DateTime())->modify($modify);
        $cookie = new Cookie($name, (string) $value, $expire);
        $response->headers->setCookie($cookie);
    }

    /**
     * Add or remove a cookie depending on the value. If value is null or empty the cookie is removed.
     */
    protected function updateCookie(Response $response, string $key, mixed $value, string $prefix = '', string $modify = '+1 year'): void
    {
        if (null === $value || '' === (string) $value) {
            $this->clearCookie($response, $key, $prefix);
        } else {
            $this->setCookie($response, $key, $value, $prefix, $modify);
        }
    }
}
