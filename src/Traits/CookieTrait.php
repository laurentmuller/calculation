<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Traits;

use App\Table\DataResults;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait to manage cookies.
 *
 * @author Laurent Muller
 */
trait CookieTrait
{
    protected function clearCookie(Response $response, string $key, string $prefix = ''): void
    {
        $name = $this->getCookieName($key, $prefix);
        $response->headers->clearCookie($name);
    }

    protected function getCookieName(string $key, string $prefix = ''): string
    {
        return '' === $prefix ? \strtoupper($key) : \strtoupper("{$prefix}_{$key}");
    }

    /**
     * @param string|int|float|bool|null $default the default value if the result parameter is null
     */
    protected function saveCookie(Response $response, DataResults $results, string $key, $default = null, string $prefix = '', string $modify = '+1 year'): void
    {
        /** @psalm-var string|int|float|bool|array|null $value */
        $value = $results->getParams($key, $default);
        if (null !== $value) {
            $this->setCookie($response, $key, $value, $prefix, $modify);
        } else {
            $this->clearCookie($response, $key, $prefix);
        }
    }

    /**
     * @param mixed $value the cookie value
     */
    protected function setCookie(Response $response, string $key, $value, string $prefix = '', string $modify = '+1 year'): void
    {
        $name = $this->getCookieName($key, $prefix);
        $expire = (new \DateTime())->modify($modify);
        $cookie = new Cookie($name, (string) $value, $expire);
        $response->headers->setCookie($cookie);
    }
}
