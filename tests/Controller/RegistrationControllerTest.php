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

namespace App\Tests\Controller;

use App\Service\EmailVerifier;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;

final class RegistrationControllerTest extends ControllerTestCase
{
    private bool $throwOnHandleEmail = false;
    private bool $throwOnSendMail = false;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->throwOnHandleEmail = false;
        $this->throwOnSendMail = false;
        $verifier = $this->createMock(EmailVerifier::class);
        $verifier->method('sendEmail')
            ->willReturnCallback(fn (): bool => $this->sendMail());
        $verifier->method('handleEmail')
            ->willReturnCallback(fn (): bool => $this->handleEmail());
        $this->setService(EmailVerifier::class, $verifier);
    }

    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/register', self::ROLE_USER];
        yield ['/register', self::ROLE_ADMIN];
        yield ['/register', self::ROLE_SUPER_ADMIN];
        yield ['/register/verify', self::ROLE_USER, Response::HTTP_FOUND];
        yield ['/register/verify', self::ROLE_ADMIN, Response::HTTP_FOUND];
        yield ['/register/verify', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND];
    }

    public function testRegister(): void
    {
        $data = [
            'username' => 'user_name_1',
            'email' => 'email_1@email.com',
            'plainPassword[first]' => '12345@#POA457az',
            'plainPassword[second]' => '12345@#POA457az',
            'agreeTerms' => 1,
        ];
        $this->checkForm(
            uri: '/register',
            id: 'registration.register.submit',
            data: $data,
            userName: self::ROLE_SUPER_ADMIN,
            disableReboot: true,
        );
    }

    public function testRegisterWithException(): void
    {
        $this->throwOnSendMail = true;
        $data = [
            'username' => 'user_name_2',
            'email' => 'email_2@email.com',
            'plainPassword[first]' => '12345@#POA457az',
            'plainPassword[second]' => '12345@#POA457az',
            'agreeTerms' => 1,
        ];
        $this->checkForm(
            uri: '/register',
            id: 'registration.register.submit',
            data: $data,
            userName: self::ROLE_SUPER_ADMIN,
            disableReboot: true,
        );
    }

    public function testVerify(): void
    {
        $this->checkRoute(
            url: \sprintf('/register/verify?id=%d', self::ID_SUPER_ADMIN),
            username: self::ROLE_SUPER_ADMIN,
            expected: Response::HTTP_FOUND,
        );
    }

    public function testVerifyWithException(): void
    {
        $this->throwOnHandleEmail = true;
        $this->checkRoute(
            url: \sprintf('/register/verify?id=%d', self::ID_SUPER_ADMIN),
            username: self::ROLE_SUPER_ADMIN,
            expected: Response::HTTP_FOUND,
        );
    }

    private function handleEmail(): bool
    {
        if ($this->throwOnHandleEmail) {
            throw new UnexpectedResponseException();
        }

        return $this->throwOnHandleEmail;
    }

    private function sendMail(): bool
    {
        if ($this->throwOnSendMail) {
            throw new UnexpectedResponseException();
        }

        return $this->throwOnSendMail;
    }
}
