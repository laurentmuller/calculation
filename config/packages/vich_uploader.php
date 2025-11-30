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

use App\Service\UserNamer;

return App::config([
    'vich_uploader' => [
        'db_driver' => 'orm',
        'mappings' => [
            'user_image' => [
                'namer' => UserNamer::class,
                'uri_prefix' => '/images/users',
            ],
        ],
    ],
]);
