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

namespace App\Attribute;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Shortcut route attribute for the 'GET' and 'POST' methods.
 *
 * @see Request::METHOD_GET
 * @see Request::METHOD_POST
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class GetPostRoute extends Route
{
    /**
     * @param array<string|\Stringable> $requirements
     */
    public function __construct(string $path, string $name, array $requirements = [])
    {
        parent::__construct(
            path: $path,
            name: $name,
            requirements: $requirements,
            methods: [Request::METHOD_GET, Request::METHOD_POST]
        );
    }
}
