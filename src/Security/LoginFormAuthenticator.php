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

namespace App\Security;

use App\Controller\SecurityController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Login form authenticator.
 *
 * @author Laurent Muller
 */
class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    private HttpUtils $utils;

    /**
     * Constructor.
     */
    public function __construct(HttpUtils $utils)
    {
        $this->utils = $utils;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(Request $request): PassportInterface
    {
        $username = $request->get('username', '');
        $password = $request->get('password', '');
        $csrf_token = $request->get('_csrf_token', '');

        $request->getSession()->set(Security::LAST_USERNAME, $username);

        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrf_token),
                new RememberMeBadge(),
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($request->hasSession() && $targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->getHomeUrl($request));
    }

    /**
     * {@inheritDoc}
     */
    public function supports(Request $request): bool
    {
        return $request->isMethod(Request::METHOD_POST) &&
            $this->utils->checkRequestPath($request, SecurityController::LOGIN_ROUTE);
    }

    /**
     * Return the URL to the index (home) page.
     */
    protected function getHomeUrl(Request $request): string
    {
        return $this->utils->generateUri($request, SecurityController::HOME_PAGE);
    }

    /**
     * {@inheritDoc}
     */
    protected function getLoginUrl(Request $request): string
    {
        return $this->utils->generateUri($request, SecurityController::LOGIN_ROUTE);
    }
}
