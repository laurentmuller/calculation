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
use Symfony\Component\Clock\DatePoint;
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
     * @phpstan-return \Generator<int, array{0: \Exception, 1: string, 2?: 1}>
     */
    public static function getExceptions(): \Generator
    {
        // user registration
        yield [new ExpiredSignatureException(), 'registration.expired_signature'];
        yield [new InvalidSignatureException(), 'registration.invalid_signature'];
        yield [new WrongEmailVerifyException(), 'registration.wrong_email_verify'];
        // reset password
        yield [new ExpiredResetPasswordTokenException(), 'reset.expired_reset_password_token'];
        yield [new InvalidResetPasswordTokenException(), 'reset.invalid_reset_password_token'];
        yield [new TooManyPasswordRequestsException(new DatePoint('2000-01-01')), 'reset.too_many_password_request', 1];
        // error
        yield [new TransportException(), 'error.send_email'];
        yield [new \Exception(), 'error.unknown'];
    }

    #[DataProvider('getExceptions')]
    public function testException(\Exception $e, string $message, int $messageData = 0): void
    {
        $result = $this->mapException($e);
        self::assertSame(0, $result->getCode());
        self::assertSame($message, $result->getMessage());
        self::assertSame($message, $result->getMessageKey());
        self::assertCount($messageData, $result->getMessageData());
        self::assertInstanceOf($e::class, $result->getPrevious());
    }

    private function mapException(\Exception $e): CustomUserMessageAuthenticationException
    {
        $this->service->handleException($this->request, $e);
        $result = $this->request->getSession()->get(SecurityRequestAttributes::AUTHENTICATION_ERROR);
        self::assertInstanceOf(CustomUserMessageAuthenticationException::class, $result);

        return $result;
    }
}
