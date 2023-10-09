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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait to manage cookies.
 */
trait CookieTrait
{
    /**
     * Clears a cookie in the browser.
     */
    protected function clearCookie(Response $response, string $key, string $prefix = '', string $path = '/', bool $httpOnly = true): void
    {
        $name = $this->getCookieName($key, $prefix);
        $response->headers->clearCookie(name: $name, path: $path, httpOnly: $httpOnly);
    }

    protected function getCookieBoolean(Request $request, string $key, string $prefix = '', bool $default = false): bool
    {
        $name = $this->getCookieName($key, $prefix);

        return $request->cookies->getBoolean($name, $default);
    }

    /**
     * Returns the cookie value converted to an enum.
     *
     * @template EnumType of \BackedEnum
     *
     * @psalm-param EnumType $default
     *
     * @psalm-return EnumType
     */
    protected function getCookieEnum(Request $request, string $key, \BackedEnum $default, string $prefix = ''): \BackedEnum
    {
        $name = $this->getCookieName($key, $prefix);

        return $request->cookies->getEnum($name, $default::class, $default) ?? $default;
    }

    protected function getCookieFloat(Request $request, string $key, string $prefix = '', float $default = 0): float
    {
        $name = $this->getCookieName($key, $prefix);

        return (float) $request->cookies->get($name, (string) $default);
    }

    protected function getCookieInt(Request $request, string $key, string $prefix = '', int $default = 0): int
    {
        $name = $this->getCookieName($key, $prefix);

        return $request->cookies->getInt($name, $default);
    }

    /**
     * Gets the cookie name.
     */
    protected function getCookieName(string $key, string $prefix = ''): string
    {
        return '' === $prefix ? \strtoupper($key) : \strtoupper($prefix . '_' . $key);
    }

    /**
     * @psalm-return ($default is null ? (string|null) : string)
     */
    protected function getCookieString(Request $request, string $key, string $prefix = '', string $default = null): string|null
    {
        $name = $this->getCookieName($key, $prefix);

        return $request->cookies->get($name, $default);
    }

    /**
     * Sets a cookie in the browser.
     */
    protected function setCookie(Response $response, string $key, mixed $value, string $prefix = '', string $path = '/', string $modify = '+1 year', bool $httpOnly = true): void
    {
        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        } elseif (\is_bool($value)) {
            $value = (int) $value;
        }

        $name = $this->getCookieName($key, $prefix);
        $expire = (new \DateTime())->modify($modify);
        $cookie = new Cookie(name: $name, value: (string) $value, expire: $expire, path: $path, httpOnly: $httpOnly);
        $response->headers->setCookie($cookie);
    }

    /**
     * Add or remove a cookie depending on the value. If value is null or empty ('') the cookie is removed.
     */
    protected function updateCookie(Response $response, string $key, mixed $value, string $prefix = '', string $path = '/', string $modify = '+1 year', bool $httpOnly = true): void
    {
        if ($value instanceof \BackedEnum || \is_bool($value) || '' !== (string) $value) {
            $this->setCookie($response, $key, $value, $prefix, $path, $modify, $httpOnly);
        } else {
            $this->clearCookie($response, $key, $prefix, $path, $httpOnly);
        }
    }
}
