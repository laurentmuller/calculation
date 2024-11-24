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

    protected function getParamBoolean(Request $request, string $key, string $prefix = '', bool $default = false): bool
    {
        $default = $this->getCookieBoolean($request, $key, $prefix, $default);

        return $this->getRequestBoolean($request, $key, $default);
    }

    /**
     * Returns the parameter value converted to an enum.
     *
     * @template EnumType of \BackedEnum
     *
     * @psalm-param EnumType $default
     *
     * @psalm-return EnumType
     */
    protected function getParamEnum(Request $request, string $key, \BackedEnum $default, string $prefix = ''): \BackedEnum
    {
        $default = $this->getCookieEnum($request, $key, $default, $prefix);

        return $this->getRequestEnum($request, $key, $default);
    }

    protected function getParamFloat(Request $request, string $key, string $prefix = '', float $default = 0): float
    {
        $default = $this->getCookieFloat($request, $key, $prefix, $default);

        return $this->getRequestFloat($request, $key, $default);
    }

    protected function getParamInt(Request $request, string $key, string $prefix = '', int $default = 0): int
    {
        $default = $this->getCookieInt($request, $key, $prefix, $default);

        return $this->getRequestInt($request, $key, $default);
    }

    protected function getParamString(Request $request, string $key, string $prefix = '', string $default = ''): string
    {
        $default = $this->getCookieString($request, $key, $prefix, $default);

        return $this->getRequestString($request, $key, $default);
    }
}
