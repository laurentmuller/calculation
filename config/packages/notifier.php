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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Enums\Importance;
use App\Service\ApplicationService;

/** @var array<string, string> $channel_policy */
$channel_policy = \array_reduce(
    Importance::cases(),
    static fn (array $carry, Importance $importance): array => $carry + [$importance->value => 'email'],
    []
);

return App::config([
    'framework' => [
        'notifier' => [
            'channel_policy' => $channel_policy,
            'admin_recipients' => [
                [
                    'email' => ApplicationService::OWNER_EMAIL,
                ],
            ],
        ],
    ],
]);
