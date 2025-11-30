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

use App\Repository\UserRepository;

return App::config([
    'symfonycasts_reset_password' => [
        'request_password_repository' => UserRepository::class,
        'throttle_limit' => 300,
    ],
]);
