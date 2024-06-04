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

use App\Controller\SecurityController;
use App\Entity\User;
use App\Interfaces\RoleInterface;
use App\Listener\ResponseListener;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Config\SecurityConfig;

return static function (SecurityConfig $config): void {
    // hasher
    $config->passwordHasher(PasswordAuthenticatedUserInterface::class)
        ->algorithm('auto');
    $config->passwordHasher(User::class)
        ->algorithm('auto');

    // roles
    $config->roleHierarchy(RoleInterface::ROLE_ADMIN, RoleInterface::ROLE_USER)
        ->roleHierarchy(RoleInterface::ROLE_SUPER_ADMIN, [RoleInterface::ROLE_ADMIN]);

    // user provider
    $config->provider('app_user_provider')
        ->entity()
        ->class(User::class)
        ->property('username');

    // dev firewall
    $config->firewall(ResponseListener::FIREWALL_DEV)
        ->pattern('^/(_(profiler|wdt)|css|images|js)/')
        ->security(false);

    // main firewall
    $firewall = $config->firewall(ResponseListener::FIREWALL_MAIN)
        ->lazy(true);

    // allows 5 login attempts per minute
    $firewall->loginThrottling();

    // switch user
    $firewall->switchUser()
        ->role(RoleInterface::ROLE_SUPER_ADMIN);

    // login
    $firewall->formLogin()
        ->loginPath(SecurityController::LOGIN_ROUTE)
        ->checkPath(SecurityController::LOGIN_ROUTE)
        ->usernameParameter('username')
        ->passwordParameter('password')
        ->csrfParameter('login_token')
        ->enableCsrf(true);

    // logout
    $firewall->logout()
        ->path(SecurityController::LOGOUT_ROUTE)
        ->target(SecurityController::SUCCESS_ROUTE)
        ->csrfParameter('logout_token')
        ->enableCsrf(true);

    // remember me
    $firewall->rememberMe()
        ->signatureProperties(['email', 'password'])
        ->rememberMeParameter('remember_me')
        ->secret('%app_secret%')
        ->path('%cookie_path%')
        ->lifetime(2_592_000) // 30 days
        ->samesite(Cookie::SAMESITE_LAX)
        ->secure(true);

    // access control
    /** @psalm-var array<string, string[]> $access */
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
    $channel = '%env(string:CHANNEL)%';
    foreach ($access as $role => $paths) {
        foreach ($paths as $path) {
            $config->accessControl()
                ->requiresChannel($channel)
                ->roles($role)
                ->path($path);
        }
    }
};
