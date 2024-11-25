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
    protected function getCookieBoolean(Request $request, string $key, string $prefix = '', bool $default = false): bool
    {
        return $request->cookies->getBoolean($this->getCookieName($key, $prefix), $default);
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
    protected function getCookieEnum(
        Request $request,
        string $key,
        \BackedEnum $default,
        string $prefix = ''
    ): \BackedEnum {
        return $request->cookies->getEnum($this->getCookieName($key, $prefix), $default::class, $default);
    }

    protected function getCookieFloat(
        Request $request,
        string $key,
        string $prefix = '',
        float $default = 0
    ): float {
        return (float) $request->cookies->get($this->getCookieName($key, $prefix), (string) $default);
    }

    protected function getCookieInt(
        Request $request,
        string $key,
        string $prefix = '',
        int $default = 0
    ): int {
        return $request->cookies->getInt($this->getCookieName($key, $prefix), $default);
    }

    protected function getCookieString(
        Request $request,
        string $key,
        string $prefix = '',
        string $default = ''
    ): string {
        return $request->cookies->getString($this->getCookieName($key, $prefix), $default);
    }

    /**
     * Add or remove a cookie depending on the value.
     *
     * If the value is null or empty (''), the cookie is removed.
     */
    protected function updateCookie(
        Response $response,
        string $key,
        string|bool|int|float|\BackedEnum|null $value,
        string $prefix = '',
        string $path = '/',
        string $modify = '+1 year',
        bool $httpOnly = true
    ): void {
        if (null === $value || '' === $value) {
            $this->clearCookie($response, $key, $prefix, $path, $httpOnly);
        } else {
            $this->setCookie($response, $key, $value, $prefix, $path, $modify, $httpOnly);
        }
    }

    /**
     * Clears a cookie in the browser.
     */
    private function clearCookie(
        Response $response,
        string $key,
        string $prefix,
        string $path,
        bool $httpOnly
    ): void {
        $name = $this->getCookieName($key, $prefix);
        $response->headers->clearCookie(name: $name, path: $path, httpOnly: $httpOnly);
    }

    private function getCookieExpire(string $modify): \DateTimeInterface
    {
        return (new \DateTime())->modify($modify);
    }

    /**
     * Gets the cookie name.
     */
    private function getCookieName(string $key, string $prefix = ''): string
    {
        return '' === $prefix ? \strtoupper($key) : \strtoupper($prefix . '_' . $key);
    }

    /**
     * Sets a cookie in the browser.
     */
    private function setCookie(
        Response $response,
        string $key,
        string|bool|int|float|\BackedEnum|null $value,
        string $prefix,
        string $path,
        string $modify,
        bool $httpOnly
    ): void {
        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        } elseif (\is_bool($value)) {
            $value = \json_encode($value);
        }

        $cookie = Cookie::create(
            name: $this->getCookieName($key, $prefix),
            value: (string) $value,
            expire: $this->getCookieExpire($modify),
            path: $path,
            httpOnly: $httpOnly
        );
        $response->headers->setCookie($cookie);
    }
}
