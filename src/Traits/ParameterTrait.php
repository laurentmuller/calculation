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
 * Trait to get values from cookie and/or request.
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

    protected function getParamString(Request $request, string $key, string $prefix = '', string|null $default = null): string|null
    {
        $default = $this->getCookieString($request, $key, $prefix, $default);

        return $this->getRequestString($request, $key, $default);
    }
}
