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

namespace App\Security;

use App\Controller\SecurityController;
use App\Form\User\AbstractUserCaptchaType;
use App\Form\User\UserLoginType;
use App\Service\ApplicationService;
use App\Service\CaptchaImageService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PasswordUpgradeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    /**
     * @param UserProviderInterface<UserInterface> $userProvider
     */
    public function __construct(
        private readonly ApplicationService $applicationService,
        private readonly CaptchaImageService $captchaImageService,
        private readonly UserProviderInterface $userProvider,
        private readonly HttpUtils $httpUtils,
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $this->validateCaptcha($request);
        $credentials = $this->getCredentials($request);
        $passport = new Passport(
            new UserBadge($credentials[UserLoginType::USER_FIELD], $this->userProvider->loadUserByIdentifier(...)),
            new PasswordCredentials($credentials[UserLoginType::PASSWORD_FIELD]),
            [
                new RememberMeBadge(),
                new CsrfTokenBadge(SecurityController::AUTHENTICATE_TOKEN, $credentials[SecurityController::LOGIN_TOKEN]),
            ]
        );
        if ($this->userProvider instanceof PasswordUpgraderInterface) {
            $passport->addBadge(new PasswordUpgradeBadge($credentials[UserLoginType::PASSWORD_FIELD], $this->userProvider));
        }

        return $passport;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function supports(Request $request): bool
    {
        return $request->isMethod(Request::METHOD_POST)
            && 'form' === $request->getContentTypeFormat()
            && $this->httpUtils->checkRequestPath($request, SecurityController::LOGIN_ROUTE);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->httpUtils->generateUri($request, SecurityController::LOGIN_ROUTE);
    }

    /**
     * @return array{username: non-empty-string, password: non-empty-string, login_token: non-empty-string}
     */
    private function getCredentials(Request $request): array
    {
        $credentials = [];
        $credentials[UserLoginType::USER_FIELD] = $request->request->getString(UserLoginType::USER_FIELD);
        $credentials[UserLoginType::PASSWORD_FIELD] = $request->request->getString(UserLoginType::PASSWORD_FIELD);
        $credentials[SecurityController::LOGIN_TOKEN] = $request->request->getString(SecurityController::LOGIN_TOKEN);

        if ($request->hasSession()) {
            $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $credentials[UserLoginType::USER_FIELD]);
        }

        if ('' === $credentials[UserLoginType::USER_FIELD]) {
            throw new BadCredentialsException('The username must be a non-empty string.');
        }
        if ('' === $credentials[UserLoginType::PASSWORD_FIELD]) {
            throw new BadCredentialsException('The password must be a non-empty string.');
        }
        if ('' === $credentials[SecurityController::LOGIN_TOKEN]) {
            throw new BadRequestHttpException('The token must be a non-empty string.');
        }

        return $credentials;
    }

    private function validateCaptcha(Request $request): void
    {
        if (!$this->applicationService->isDisplayCaptcha()) {
            return;
        }

        $captcha = $request->request->getString(AbstractUserCaptchaType::CAPTCHA_FIELD);
        if ('' === $captcha || !$this->captchaImageService->validateToken($captcha)) {
            throw new CustomUserMessageAuthenticationException('captcha.invalid');
        }
        if (!$this->captchaImageService->validateTimeout()) {
            throw new CustomUserMessageAuthenticationException('captcha.timeout');
        }
    }
}
