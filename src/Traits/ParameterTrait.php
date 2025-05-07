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

use Symfony\Component\HttpFoundation\Request;

/**
 * Trait to get values from a cookie and/or a request.
 */
trait ParameterTrait
{
    use CookieTrait;
    use RequestTrait;

    protected function getParamBoolean(Request $request, string $key, bool $default = false, string $prefix = ''): bool
    {
        $default = $this->getCookieBoolean($request, $key, $default, $prefix);

        return $this->getRequestBoolean($request, $key, $default);
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
    protected function getParamEnum(Request $request, string $key, \BackedEnum $default, string $prefix = ''): \BackedEnum
    {
        $default = $this->getCookieEnum($request, $key, $default, $prefix);

        return $this->getRequestEnum($request, $key, $default);
    }

    protected function getParamFloat(Request $request, string $key, float $default = 0.0, string $prefix = ''): float
    {
        $default = $this->getCookieFloat($request, $key, $default, $prefix);

        return $this->getRequestFloat($request, $key, $default);
    }

    protected function getParamInt(Request $request, string $key, int $default = 0, string $prefix = ''): int
    {
        $default = $this->getCookieInt($request, $key, $default, $prefix);

        return $this->getRequestInt($request, $key, $default);
    }

    protected function getParamString(Request $request, string $key, string $default = '', string $prefix = ''): string
    {
        $default = $this->getCookieString($request, $key, $default, $prefix);

        return $this->getRequestString($request, $key, $default);
    }
}
