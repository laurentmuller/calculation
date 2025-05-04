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

namespace App\Tests\Service;

use App\Service\UserExceptionService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use SymfonyCasts\Bundle\ResetPassword\Exception\ExpiredResetPasswordTokenException;
use SymfonyCasts\Bundle\ResetPassword\Exception\InvalidResetPasswordTokenException;
use SymfonyCasts\Bundle\ResetPassword\Exception\TooManyPasswordRequestsException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\ExpiredSignatureException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\InvalidSignatureException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\WrongEmailVerifyException;

class UserExceptionServiceTest extends TestCase
{
    private Request $request;
    private UserExceptionService $service;

    #[\Override]
    protected function setUp(): void
    {
        $this->service = new UserExceptionService();
        $storage = new MockArraySessionStorage();
        $session = new Session($storage);
        $this->request = new Request();
        $this->request->setSession($session);
    }

    /**
     * @phpstan-return \Generator<int, array{0: \Throwable, 1: string, 2?: 1}>
     */
    public static function getExceptions(): \Generator
    {
        // register user
        yield [new ExpiredSignatureException(), 'registration_expired_signature'];
        yield [new InvalidSignatureException(), 'registration_invalid_signature'];
        yield [new WrongEmailVerifyException(), 'registration_wrong_email_verify'];
        // reset password
        yield [new ExpiredResetPasswordTokenException(), 'reset_expired_reset_password_token'];
        yield [new InvalidResetPasswordTokenException(), 'reset_invalid_reset_password_token'];
        yield [new TooManyPasswordRequestsException(new \DateTime('2000-01-01')), 'reset_too_many_password_request', 1];
        // mailer
        yield [new TransportException(), 'send_email_error'];
        // other
        yield [new \Exception(), 'error_unknown'];
    }

    #[DataProvider('getExceptions')]
    public function testException(\Throwable $e, string $message, int $messageData = 0): void
    {
        $result = $this->mapException($e);
        self::assertSame(0, $result->getCode());
        self::assertSame($message, $result->getMessage());
        self::assertSame($message, $result->getMessageKey());
        self::assertCount($messageData, $result->getMessageData());
        self::assertInstanceOf($e::class, $result->getPrevious());
    }

    private function mapException(\Throwable $e): CustomUserMessageAuthenticationException
    {
        $this->service->handleException($this->request, $e);
        $result = $this->request->getSession()->get(SecurityRequestAttributes::AUTHENTICATION_ERROR);
        self::assertInstanceOf(CustomUserMessageAuthenticationException::class, $result);

        return $result;
    }
}
