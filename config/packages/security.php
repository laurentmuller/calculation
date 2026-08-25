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

use App\Constants\CacheAttributes;
use App\Constants\SecurityAttributes;
use App\Interfaces\RoleInterface;
use App\Listener\SwitchUserListener;
use App\Repository\UserRepository;
use App\Security\LoginFormAuthenticator;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

return App::config([
    'security' => [
        'password_hashers' => [
            PasswordAuthenticatedUserInterface::class => 'auto',
        ],
        'role_hierarchy' => [
            RoleInterface::ROLE_ADMIN => [RoleInterface::ROLE_USER],
            RoleInterface::ROLE_SUPER_ADMIN => [RoleInterface::ROLE_ADMIN],
        ],
        'providers' => [
            'user_provider' => ['id' => UserRepository::class],
        ],
        'firewalls' => [
            SecurityAttributes::DEV_FIREWALL => [
                'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
                'security' => false,
            ],
            SecurityAttributes::MAIN_FIREWALL => [
                'lazy' => true,
                'provider' => 'user_provider',
                'entry_point' => LoginFormAuthenticator::class,
                'custom_authenticators' => [LoginFormAuthenticator::class],
                'login_throttling' => [], // allows 5 login attempts per minute
                'switch_user' => [
                    'role' => RoleInterface::ROLE_SUPER_ADMIN,
                    'parameter' => SwitchUserListener::SWITCH_USER_PARAMETER,
                ],
                'logout' => [
                    'csrf_parameter' => SecurityAttributes::LOGOUT_TOKEN,
                    'enable_csrf' => true,
                ],
                'remember_me' => [
                    'signature_properties' => ['email', 'password'],
                    'remember_me_parameter' => SecurityAttributes::REMEMBER_FIELD,
                    'secret' => '%env(string:APP_SECRET)%',
                    'path' => '%env(string:COOKIE_PATH)%',
                    'lifetime' => CacheAttributes::LIFE_TIME_ONE_MONTH,
                    'samesite' => Cookie::SAMESITE_LAX,
                    'secure' => true,
                ],
            ],
        ],
    ],
    'when@test' => [
        'security' => [
            'password_hashers' => [
                PasswordAuthenticatedUserInterface::class => 'plaintext',
            ],
        ],
    ],
]);
