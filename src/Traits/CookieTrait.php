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

use App\Utils\DateUtils;
use fpdf\Interfaces\PdfEnumDefaultInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait to manage cookies.
 */
trait CookieTrait
{
    protected function getCookieBoolean(
        Request $request,
        string $key,
        bool $default = false,
        string $prefix = ''
    ): bool {
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
        float $default = 0.0,
        string $prefix = ''
    ): float {
        return (float) $request->cookies->get($this->getCookieName($key, $prefix), (string) $default);
    }

    protected function getCookieInt(
        Request $request,
        string $key,
        int $default = 0,
        string $prefix = ''
    ): int {
        return $request->cookies->getInt($this->getCookieName($key, $prefix), $default);
    }

    /**
     * Gets the cookie path.
     */
    abstract protected function getCookiePath(): string;

    protected function getCookieString(
        Request $request,
        string $key,
        string $default = '',
        string $prefix = ''
    ): string {
        return $request->cookies->getString($this->getCookieName($key, $prefix), $default);
    }

    /**
     * Add or remove a cookie depending on the value.
     *
     * If the value is null, empty ('') or is the default enumeration, the cookie is removed.
     */
    protected function updateCookie(
        Response $response,
        string $key,
        string|bool|int|float|\BackedEnum|null $value,
        string $prefix = '',
        bool $httpOnly = true
    ): void {
        if ($value instanceof PdfEnumDefaultInterface && $value->isDefault()) {
            $value = null;
        }
        if (null === $value || '' === $value) {
            $this->clearCookie($response, $key, $prefix, $httpOnly);
        } else {
            $this->setCookie($response, $key, $value, $prefix, $httpOnly);
        }
    }

    /**
     * Add or remove cookies depending on the values.
     *
     * @param array<string, string|bool|int|float|\BackedEnum|null> $values
     */
    protected function updateCookies(
        Response $response,
        array $values,
        string $prefix = '',
        bool $httpOnly = true
    ): void {
        foreach ($values as $key => $value) {
            $this->updateCookie($response, $key, $value, $prefix, $httpOnly);
        }
    }

    /**
     * Clears a cookie in the browser.
     */
    private function clearCookie(
        Response $response,
        string $key,
        string $prefix,
        bool $httpOnly
    ): void {
        $response->headers->clearCookie(
            name: $this->getCookieName($key, $prefix),
            path: $this->getCookiePath(),
            httpOnly: $httpOnly
        );
    }

    /**
     * Gets the cookie expiration date. The default value is now plus 1 year.
     */
    private function getCookieExpire(): \DateTimeInterface
    {
        return DateUtils::modify(new \DateTime(), '+1 year');
    }

    /**
     * Gets the cookie name.
     */
    private function getCookieName(string $key, string $prefix = ''): string
    {
        return \strtoupper('' === $prefix ? $key : $prefix . '_' . $key);
    }

    /**
     * Sets a cookie in the browser.
     */
    private function setCookie(
        Response $response,
        string $key,
        string|bool|int|float|\BackedEnum $value,
        string $prefix,
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
            expire: $this->getCookieExpire(),
            path: $this->getCookiePath(),
            httpOnly: $httpOnly
        );
        $response->headers->setCookie($cookie);
    }
}
