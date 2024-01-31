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

use App\Entity\User;
use App\Interfaces\RoleInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Config\SecurityConfig;
use Symfony\Component\HttpFoundation\Cookie;

return static function (SecurityConfig $config): void {
    // hasher
    $config->passwordHasher(PasswordAuthenticatedUserInterface::class)
        ->algorithm('auto');
    $config->passwordHasher(User::class)
        ->algorithm('auto');

    // roles
    $config->roleHierarchy(RoleInterface::ROLE_ADMIN, RoleInterface::ROLE_USER)
        ->roleHierarchy(RoleInterface::ROLE_SUPER_ADMIN, [RoleInterface::ROLE_ADMIN, 'ROLE_ALLOWED_TO_SWITCH']);

    // user provider
    $config->provider('app_user_provider')
        ->entity()
        ->class(User::class)
        ->property('username');

    // dev firewall
    $config->firewall('dev')
        ->pattern('^/(_(profiler|wdt)|css|images|js)/')
        ->security(false);

    // main firewall
    $firewall = $config->firewall('main');
    $firewall->lazy(true)
        ->switchUser();

    // login
    $firewall->formLogin()
        ->loginPath('app_login')
        ->checkPath('app_login')
        ->enableCsrf(true)
        ->usernameParameter('username')
        ->passwordParameter('password');

    // logout
    $firewall->logout()
        ->path('app_logout')
        ->enableCsrf(true)
        ->target('app_logout_success');

    // remember me
    $firewall->rememberMe()
        ->signatureProperties(['email', 'password'])
        ->rememberMeParameter('remember_me')
        ->secret('%app_secret%')
        ->path('%cookie_path%')
        ->lifetime(2_592_000) // 30 days
        ->samesite(Cookie::SAMESITE_LAX)
        ->secure(true);

    // channel
    $channel = '%env(SECURE_SCHEME)%';

    // public
    $paths = [
        '^/login',
        '^/logout/success',
        '^/captcha',
        '^/about/policy',
        '^/about/licence',
        '^/reset-password',
        '^/ajax/check/user',
    ];
    foreach ($paths as $path) {
        $config->accessControl()->path($path)
            ->roles(AuthenticatedVoter::PUBLIC_ACCESS)
            ->requiresChannel($channel);
    }

    // admin
    $config->accessControl()->path('^/admin/')
        ->roles(RoleInterface::ROLE_ADMIN)
        ->requiresChannel($channel);

    // default
    $config->accessControl()->path('^/')
        ->roles(RoleInterface::ROLE_USER)
        ->requiresChannel($channel);
};
