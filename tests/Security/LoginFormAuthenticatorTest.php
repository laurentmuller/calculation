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

use App\Security\LoginFormAuthenticator;
use App\Security\SecurityAttributes;
use App\Service\ApplicationService;
use App\Service\CaptchaImageService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\HttpUtils;

class LoginFormAuthenticatorTest extends TestCase
{
    public static function getSupports(): \Generator
    {
        $request = self::createRequest(method: Request::METHOD_GET);
        yield [$request, false];

        $request = self::createRequest(contentType: 'text/plain');
        yield [$request, false];

        $request = self::createRequest();
        yield [$request, true];
    }

    /**
     * @throws Exception
     */
    public function testAuthenticate(): void
    {
        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils->method('checkRequestPath')
            ->willReturn(true);
        $authenticator = $this->createAuthenticator(httpUtils: $httpUtils);
        $values = [
            SecurityAttributes::USER_FIELD => 'username',
            SecurityAttributes::PASSWORD_FIELD => 'password',
            SecurityAttributes::LOGIN_TOKEN => 'token',
        ];
        $request = self::createRequest(values: $values);
        $request->setSession(new Session(new MockArraySessionStorage()));

        $actual = $authenticator->authenticate($request);
        self::assertInstanceOf(Passport::class, $actual);
    }

    /**
     * @throws Exception
     */
    public function testAuthenticateEmptyPassword(): void
    {
        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils->method('checkRequestPath')
            ->willReturn(true);
        $authenticator = $this->createAuthenticator(httpUtils: $httpUtils);
        $values = [
            SecurityAttributes::USER_FIELD => 'username',
            SecurityAttributes::PASSWORD_FIELD => '',
            SecurityAttributes::LOGIN_TOKEN => 'token',
        ];
        $request = self::createRequest(values: $values);

        self::expectException(BadCredentialsException::class);
        $authenticator->authenticate($request);
    }

    /**
     * @throws Exception
     */
    public function testAuthenticateEmptyUserName(): void
    {
        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils->method('checkRequestPath')
            ->willReturn(true);
        $authenticator = $this->createAuthenticator(httpUtils: $httpUtils);
        $values = [
            SecurityAttributes::USER_FIELD => '',
            SecurityAttributes::PASSWORD_FIELD => 'password',
            SecurityAttributes::LOGIN_TOKEN => 'token',
        ];
        $request = self::createRequest(values: $values);

        self::expectException(BadCredentialsException::class);
        $authenticator->authenticate($request);
    }

    /**
     * @throws Exception
     */
    public function testAuthenticateNoToken(): void
    {
        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils->method('checkRequestPath')
            ->willReturn(true);
        $authenticator = $this->createAuthenticator(httpUtils: $httpUtils);
        $values = [
            SecurityAttributes::USER_FIELD => 'username',
            SecurityAttributes::PASSWORD_FIELD => 'password',
            SecurityAttributes::LOGIN_TOKEN => '',
        ];
        $request = self::createRequest(values: $values);

        self::expectException(BadRequestHttpException::class);
        $authenticator->authenticate($request);
    }

    /**
     * @throws Exception
     */
    public function testAuthenticateWithUserProvider(): void
    {
        $userProvider = $this->createMock(ProviderInterface::class);
        $authenticator = $this->createAuthenticator(userProvider: $userProvider);

        $values = [
            SecurityAttributes::USER_FIELD => 'username',
            SecurityAttributes::PASSWORD_FIELD => 'password',
            SecurityAttributes::LOGIN_TOKEN => 'token',
        ];
        $request = self::createRequest(values: $values);
        $request->setSession(new Session(new MockArraySessionStorage()));

        $actual = $authenticator->authenticate($request);
        self::assertInstanceOf(Passport::class, $actual);
    }

    /**
     * @throws Exception
     */
    public function testCaptchaEmpty(): void
    {
        $applicationService = $this->createMock(ApplicationService::class);
        $applicationService->method('isDisplayCaptcha')
            ->willReturn(true);

        $authenticator = $this->createAuthenticator(applicationService: $applicationService);
        $values = [
            SecurityAttributes::USER_FIELD => 'username',
            SecurityAttributes::PASSWORD_FIELD => 'password',
            SecurityAttributes::LOGIN_TOKEN => 'token',
            SecurityAttributes::CAPTCHA_FIELD => '',
        ];
        $request = self::createRequest(values: $values);

        self::expectException(CustomUserMessageAuthenticationException::class);
        self::expectExceptionMessage('captcha.invalid');
        $authenticator->authenticate($request);
    }

    /**
     * @throws Exception
     */
    public function testCaptchaNotDisplay(): void
    {
        $applicationService = $this->createMock(ApplicationService::class);
        $applicationService->method('isDisplayCaptcha')
            ->willReturn(false);

        $authenticator = $this->createAuthenticator(applicationService: $applicationService);
        $values = [
            SecurityAttributes::USER_FIELD => 'username',
            SecurityAttributes::PASSWORD_FIELD => 'password',
            SecurityAttributes::LOGIN_TOKEN => 'token',
        ];
        $request = self::createRequest(values: $values);
        $request->setSession(new Session(new MockArraySessionStorage()));

        $actual = $authenticator->authenticate($request);
        self::assertInstanceOf(Passport::class, $actual);
    }

    /**
     * @throws Exception
     */
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
            SecurityAttributes::USER_FIELD => 'username',
            SecurityAttributes::PASSWORD_FIELD => 'password',
            SecurityAttributes::LOGIN_TOKEN => 'token',
            SecurityAttributes::CAPTCHA_FIELD => 'captcha',
        ];
        $request = self::createRequest(values: $values);

        self::expectException(CustomUserMessageAuthenticationException::class);
        self::expectExceptionMessage('captcha.timeout');
        $authenticator->authenticate($request);
    }

    /**
     * @throws Exception
     */
    public function testGetLoginUrl(): void
    {
        $expected = '/login';
        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils->method('checkRequestPath')
            ->willReturn(true);
        $httpUtils->method('generateUri')
            ->willReturn($expected);
        $authenticator = $this->createAuthenticator(httpUtils: $httpUtils);

        $actual = $authenticator->onAuthenticationFailure(new Request(), new AuthenticationException());
        self::assertInstanceOf(RedirectResponse::class, $actual);
        self::assertSame($expected, $actual->getTargetUrl());
    }

    /**
     * @throws Exception
     */
    public function testOnAuthenticationSuccess(): void
    {
        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils->method('checkRequestPath')
            ->willReturn(true);
        $authenticator = $this->createAuthenticator(httpUtils: $httpUtils);

        $request = new Request();
        $token = $this->createMock(TokenInterface::class);
        $actual = $authenticator->onAuthenticationSuccess($request, $token, 'fake');
        self::assertNull($actual);
    }

    /**
     * @throws Exception
     */
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

    /**
     * @param UserProviderInterface<UserInterface>|null $userProvider
     *
     * @throws Exception
     */
    private function createAuthenticator(
        ?ApplicationService $applicationService = null,
        ?CaptchaImageService $captchaImageService = null,
        ?UserProviderInterface $userProvider = null,
        ?HttpUtils $httpUtils = null
    ): LoginFormAuthenticator {
        $applicationService ??= $this->createMock(ApplicationService::class);
        $captchaImageService ??= $this->createMock(CaptchaImageService::class);
        $userProvider ??= $this->createMock(UserProviderInterface::class);
        $httpUtils ??= $this->createMock(HttpUtils::class);

        return new LoginFormAuthenticator(
            $applicationService,
            $captchaImageService,
            $userProvider,
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
        $request->headers->set('Content-Type', $contentType);

        return $request;
    }
}
