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
        if (null !== $input = $this->getRequestBag($request, $key)) {
            return $input->all($key);
        }

        return $default;
    }

    /**
     * Returns the request parameter value converted to boolean.
     */
    protected function getRequestBoolean(Request $request, string $key, bool $default = false): bool
    {
        if (null !== $input = $this->getRequestBag($request, $key)) {
            return $input->getBoolean($key, $default);
        }

        return $default;
    }

    /**
     * Returns the request parameter value converted to float.
     */
    protected function getRequestFloat(Request $request, string $key, float $default = 0): float
    {
        if (null !== $input = $this->getRequestBag($request, $key)) {
            return (float) $input->get($key, $default);
        }

        return $default;
    }

    /**
     * Returns the request parameter value converted to integer.
     */
    protected function getRequestInt(Request $request, string $key, int $default = 0): int
    {
        if (null !== $input = $this->getRequestBag($request, $key)) {
            return $input->getInt($key, $default);
        }

        return $default;
    }

    /**
     * Returns the request parameter value converted to string.
     */
    protected function getRequestString(Request $request, string $key, string $default = null): ?string
    {
        if (null !== $input = $this->getRequestBag($request, $key)) {
            /** @psalm-var string|null $default */
            $default = $input->get($key, $default);
        }

        return $default;
    }

    /**
     * Returns the request parameter value.
     */
    protected function getRequestValue(Request $request, string $key, string|int|float|bool|null $default = null): string|int|float|bool|null
    {
        if (null !== $input = $this->getRequestBag($request, $key)) {
            /** @psalm-var string|int|float|bool|null $default */
            $default = $input->get($key, $default);
        }

        return $default;
    }

    private function getRequestBag(Request $request, string $key): ?ParameterBag
    {
        if ($request->attributes->has($key)) {
            return $request->attributes;
        } elseif ($request->query->has($key)) {
            return $request->query;
        } elseif ($request->request->has($key)) {
            return $request->request;
        } else {
            return null;
        }
    }
}
