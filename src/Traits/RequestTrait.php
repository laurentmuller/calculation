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

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Trait to get value from a request.
 */
trait RequestTrait
{
    /**
     * Returns all the request parameters.
     */
    protected function getRequestAll(Request $request, string $key, array $default = []): array
    {
        $value = $this->getRequestBag($request, $key)->all($key);

        return [] === $value ? $default : $value;
    }

    /**
     * Returns the request parameter value converted to boolean.
     */
    protected function getRequestBoolean(Request $request, string $key, bool $default = false): bool
    {
        return $this->getRequestBag($request, $key)->getBoolean($key, $default);
    }

    /**
     * Returns the parameter value converted to an enum.
     *
     * @template EnumType of \BackedEnum
     *
     * @phpstan-param EnumType $default
     *
     * @phpstan-return EnumType
     */
    protected function getRequestEnum(Request $request, string $key, \BackedEnum $default): \BackedEnum
    {
        return $this->getRequestBag($request, $key)->getEnum($key, $default::class, $default);
    }

    /**
     * Returns the request parameter value converted to float.
     */
    protected function getRequestFloat(Request $request, string $key, float $default = 0): float
    {
        return (float) $this->getRequestBag($request, $key)->get($key, $default);
    }

    /**
     * Returns the request parameter value converted to integer.
     */
    protected function getRequestInt(Request $request, string $key, int $default = 0): int
    {
        return $this->getRequestBag($request, $key)->getInt($key, $default);
    }

    /**
     * Returns the request parameter value converted to string.
     */
    protected function getRequestString(Request $request, string $key, string $default = ''): string
    {
        return $this->getRequestBag($request, $key)->getString($key, $default);
    }

    /**
     * Return the request parameter value.
     *
     * @phpstan-return ($default is null ? (scalar|null) : scalar)
     */
    protected function getRequestValue(
        Request $request,
        string $key,
        string|int|float|bool|null $default = null
    ): string|int|float|bool|null {
        /** @phpstan-var scalar */
        return $this->getRequestBag($request, $key)->get($key, $default);
    }

    private function getRequestBag(Request $request, string $key): ParameterBag
    {
        if ($request->query->has($key)) {
            return $request->query;
        }
        if ($request->request->has($key)) {
            return $request->request;
        }
        if ($request->attributes->has($key)) {
            return $request->attributes;
        }

        return new ParameterBag();
    }
}
