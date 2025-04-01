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

namespace App\Tests\Security;

use App\Repository\UserRepository;
use App\Security\LoginFormAuthenticator;
use App\Service\ApplicationService;
use App\Service\CaptchaImageService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PasswordUpgradeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\HttpUtils;

class LoginFormAuthenticatorTest extends TestCase
{
    /**
     * @psalm-return \Generator<int, array{Request, bool}>
     */
    public static function getSupports(): \Generator
    {
        $request = self::createRequest(method: Request::METHOD_GET);
        yield [$request, false];

        $request = self::createRequest(contentType: 'text/plain');
        yield [$request, false];

        $request = self::createRequest();
        yield [$request, true];
    }

    public function testAuthenticateEmptyPassword(): void
    {
        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils->method('checkRequestPath')
            ->willReturn(true);
        $authenticator = $this->createAuthenticator(httpUtils: $httpUtils);
        $values = [
            'username' => 'username',
            'password' => '',
            'login_token' => 'token',
        ];
        $request = self::createRequest(values: $values);

        self::expectException(CustomUserMessageAuthenticationException::class);
        self::expectExceptionMessage('authenticator.empty_password');
        $authenticator->authenticate($request);
    }

    public function testAuthenticateEmptyToken(): void
    {
        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils->method('checkRequestPath')
            ->willReturn(true);
        $authenticator = $this->createAuthenticator(httpUtils: $httpUtils);
        $values = [
            'username' => 'username',
            'password' => 'password',
            'login_token' => '',
        ];
        $request = self::createRequest(values: $values);

        self::expectException(CustomUserMessageAuthenticationException::class);
        self::expectExceptionMessage('authenticator.empty_token');
        $authenticator->authenticate($request);
    }

    public function testAuthenticateEmptyUserName(): void
    {
        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils->method('checkRequestPath')
            ->willReturn(true);
        $authenticator = $this->createAuthenticator(httpUtils: $httpUtils);
        $values = [
            'username' => '',
            'password' => 'password',
            'login_token' => 'token',
        ];
        $request = self::createRequest(values: $values);

        self::expectException(CustomUserMessageAuthenticationException::class);
        self::expectExceptionMessage('authenticator.empty_user');
        $authenticator->authenticate($request);
    }

    public function testAuthenticateSuccess(): void
    {
        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils->method('checkRequestPath')
            ->willReturn(true);
        $authenticator = $this->createAuthenticator(httpUtils: $httpUtils);
        $values = [
            'username' => 'username',
            'password' => 'password',
            'login_token' => 'token',
        ];
        $request = self::createRequest(values: $values);

        $passport = $authenticator->authenticate($request);
        $this->validatePassport($passport);
    }

    public function testAuthenticateWithSession(): void
    {
        $userProvider = $this->createMock(UserRepository::class);
        $authenticator = $this->createAuthenticator(repository: $userProvider);

        $values = [
            'username' => 'username',
            'password' => 'password',
            'login_token' => 'token',
        ];
        $request = self::createRequest(values: $values);

        $passport = $authenticator->authenticate($request);
        $this->validatePassport($passport);
    }

    public function testCaptchaEmpty(): void
    {
        $applicationService = $this->createMock(ApplicationService::class);
        $applicationService->method('isDisplayCaptcha')
            ->willReturn(true);

        $authenticator = $this->createAuthenticator(applicationService: $applicationService);
        $values = [
            'username' => 'username',
            'password' => 'password',
            'login_token' => 'token',
            'captcha' => '',
        ];
        $request = self::createRequest(values: $values);

        self::expectException(CustomUserMessageAuthenticationException::class);
        self::expectExceptionMessage('captcha.invalid');
        $authenticator->authenticate($request);
    }

    public function testCaptchaNotDisplay(): void
    {
        $applicationService = $this->createMock(ApplicationService::class);
        $applicationService->method('isDisplayCaptcha')
            ->willReturn(false);

        $authenticator = $this->createAuthenticator(applicationService: $applicationService);
        $values = [
            'username' => 'username',
            'password' => 'password',
            'login_token' => 'token',
        ];
        $request = self::createRequest(values: $values);

        $passport = $authenticator->authenticate($request);
        $this->validatePassport($passport);
    }

    public function testCaptchaTimeout(): void
    {
        $applicationService = $this->createMock(ApplicationService::class);
        $applicationService->method('isDisplayCaptcha')
            ->willReturn(true);
        $captchaImageService = $this->createMock(CaptchaImageService::class);
        $captchaImageService->method('validateToken')
            ->willReturn(true);

        $authenticator = $this->createAuthenticator(
            applicationService: $applicationService,
            captchaImageService: $captchaImageService
        );
        $values = [
            'username' => 'username',
            'password' => 'password',
            'login_token' => 'token',
            'captcha' => 'captcha',
        ];
        $request = self::createRequest(values: $values);

        self::expectException(CustomUserMessageAuthenticationException::class);
        self::expectExceptionMessage('captcha.timeout');
        $authenticator->authenticate($request);
    }

    public function testGetLoginUrl(): void
    {
        $expected = '/login';
        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils->method('checkRequestPath')
            ->willReturn(true);
        $httpUtils->method('generateUri')
            ->willReturn($expected);
        $authenticator = $this->createAuthenticator(httpUtils: $httpUtils);

        $request = self::createRequest();
        $actual = $authenticator->onAuthenticationFailure($request, new AuthenticationException());
        self::assertInstanceOf(RedirectResponse::class, $actual);
        self::assertSame($expected, $actual->getTargetUrl());
    }

    public function testOnAuthenticationSuccess(): void
    {
        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils->method('checkRequestPath')
            ->willReturn(true);
        $authenticator = $this->createAuthenticator(httpUtils: $httpUtils);

        $request = self::createRequest();
        $token = $this->createMock(TokenInterface::class);
        $actual = $authenticator->onAuthenticationSuccess($request, $token, 'fake');
        self::assertNull($actual);
    }

    #[DataProvider('getSupports')]
    public function testSupports(Request $request, bool $expected): void
    {
        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils->method('checkRequestPath')
            ->willReturn(true);
        $authenticator = $this->createAuthenticator(httpUtils: $httpUtils);

        $actual = $authenticator->supports($request);
        self::assertSame($expected, $actual);
    }

    private function createAuthenticator(
        ?ApplicationService $applicationService = null,
        ?CaptchaImageService $captchaImageService = null,
        ?UserRepository $repository = null,
        ?HttpUtils $httpUtils = null
    ): LoginFormAuthenticator {
        $applicationService ??= $this->createMock(ApplicationService::class);
        $captchaImageService ??= $this->createMock(CaptchaImageService::class);
        $repository ??= $this->createMock(UserRepository::class);
        $httpUtils ??= $this->createMock(HttpUtils::class);

        return new LoginFormAuthenticator(
            $applicationService,
            $captchaImageService,
            $repository,
            $httpUtils,
        );
    }

    private static function createRequest(
        array $values = [],
        string $method = Request::METHOD_POST,
        string $contentType = 'application/x-www-form-urlencoded'
    ): Request {
        $request = new Request(request: $values);
        $request->setMethod($method);
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->headers->set('Content-Type', $contentType);

        return $request;
    }

    private function validatePassport(Passport $passport): void
    {
        $classes = [
            UserBadge::class,
            PasswordCredentials::class,
            RememberMeBadge::class,
            PasswordUpgradeBadge::class,
            CsrfTokenBadge::class,
        ];
        foreach ($classes as $class) {
            $actual = $passport->getBadge($class);
            self::assertInstanceOf($class, $actual);
        }
    }
}
