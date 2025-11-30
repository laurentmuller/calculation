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

use App\Entity\User;
use App\Interfaces\RoleInterface;
use App\Repository\UserRepository;
use App\Security\LoginFormAuthenticator;
use App\Security\SecurityAttributes;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

// access control
$access = [
    AuthenticatedVoter::PUBLIC_ACCESS => [
        '^/login',
        '^/logout/success',
        '^/captcha',
        '^/about/policy',
        '^/about/licence',
        '^/reset-password',
        '^/ajax/check/user',
    ],
    RoleInterface::ROLE_ADMIN => [
        '^/admin/',
    ],
    RoleInterface::ROLE_USER => [
        '^/',
    ],
];

$access_control = [];
foreach ($access as $role => $paths) {
    foreach ($paths as $path) {
        $access_control[] = [
            'requires_channel' => '%env(string:CHANNEL)%',
            'roles' => $role,
            'path' => $path,
        ];
    }
}

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
                'custom_authenticators' => [LoginFormAuthenticator::class],
                'entry_point' => LoginFormAuthenticator::class,
                'provider' => 'user_provider',
                'lazy' => true,
                'login_throttling' => [], // allows 5 login attempts per minute
                'switch_user' => [
                    'role' => RoleInterface::ROLE_SUPER_ADMIN,
                ],
                'logout' => [
                    'path' => SecurityAttributes::LOGOUT_ROUTE,
                    'target' => SecurityAttributes::LOGOUT_SUCCESS_ROUTE,
                    'csrf_parameter' => SecurityAttributes::LOGOUT_TOKEN,
                    'enable_csrf' => true,
                ],
                'remember_me' => [
                    'signature_properties' => ['email', 'password'],
                    'remember_me_parameter' => SecurityAttributes::REMEMBER_FIELD,
                    'secret' => '%app_secret%',
                    'path' => '%cookie_path%',
                    'lifetime' => 2_592_000, // 30 days
                    'samesite' => Cookie::SAMESITE_LAX,
                    'secure' => true,
                ],
            ],
        ],
        'access_control' => $access_control,
    ],
    'when@test' => [
        'security' => [
            'password_hashers' => [
                User::class => 'plaintext',
                PasswordAuthenticatedUserInterface::class => 'plaintext',
            ],
        ],
    ],
]);
