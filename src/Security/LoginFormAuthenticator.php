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

use App\Repository\UserRepository;
use App\Service\ApplicationService;
use App\Service\CaptchaImageService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;
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
    public function __construct(
        private readonly ApplicationService $applicationService,
        private readonly CaptchaImageService $captchaImageService,
        private readonly UserRepository $repository,
        private readonly HttpUtils $httpUtils,
    ) {
    }

    #[\Override]
    public function authenticate(Request $request): Passport
    {
        $this->validateCaptcha($request);

        $userIdentifier = $this->getUserIdentifier($request);
        $password = $this->getPassword($request);
        $token = $this->getToken($request);

        $userBadge = $this->createUserBadge($userIdentifier);
        $credentials = $this->createPasswordCredentials($password);
        $badges = $this->createBadges($password, $token);

        return new Passport($userBadge, $credentials, $badges);
    }

    #[\Override]
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    #[\Override]
    public function supports(Request $request): bool
    {
        return $request->isMethod(Request::METHOD_POST)
            && SecurityAttributes::CONTENT_TYPE_FORMAT === $request->getContentTypeFormat()
            && $this->httpUtils->checkRequestPath($request, SecurityAttributes::LOGIN_ROUTE);
    }

    #[\Override]
    protected function getLoginUrl(Request $request): string
    {
        return $this->httpUtils->generateUri($request, SecurityAttributes::LOGIN_ROUTE);
    }

    /**
     * @return BadgeInterface[]
     */
    private function createBadges(string $password, string $token): array
    {
        return [
            new RememberMeBadge(),
            new PasswordUpgradeBadge($password, $this->repository),
            new CsrfTokenBadge(SecurityAttributes::AUTHENTICATE_TOKEN, $token),
        ];
    }

    private function createPasswordCredentials(string $password): PasswordCredentials
    {
        return new PasswordCredentials($password);
    }

    private function createUserBadge(string $userIdentifier): UserBadge
    {
        return new UserBadge($userIdentifier, $this->repository->loadUserByIdentifier(...));
    }

    private function getPassword(Request $request): string
    {
        return $this->getRequestField($request, SecurityAttributes::PASSWORD_FIELD, 'authenticator.empty_password');
    }

    private function getRequestField(Request $request, string $field, string $error): string
    {
        $value = $request->request->getString($field);
        if ('' === $value) {
            throw new CustomUserMessageAuthenticationException($error);
        }

        return $value;
    }

    private function getToken(Request $request): string
    {
        return $this->getRequestField($request, SecurityAttributes::LOGIN_TOKEN, 'authenticator.empty_token');
    }

    private function getUserIdentifier(Request $request): string
    {
        $userIdentifier = $this->getRequestField($request, SecurityAttributes::USER_FIELD, 'authenticator.empty_user');
        if ($request->hasSession()) {
            $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $userIdentifier);
        }

        return $userIdentifier;
    }

    private function validateCaptcha(Request $request): void
    {
        if (!$this->applicationService->isDisplayCaptcha()) {
            return;
        }
        $captcha = $this->getRequestField($request, SecurityAttributes::CAPTCHA_FIELD, 'captcha.empty');
        if (!$this->captchaImageService->validateToken($captcha)) {
            throw new CustomUserMessageAuthenticationException('captcha.invalid');
        }
        if (!$this->captchaImageService->validateTimeout()) {
            throw new CustomUserMessageAuthenticationException('captcha.timeout');
        }
    }
}
