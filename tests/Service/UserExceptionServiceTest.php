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
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use SymfonyCasts\Bundle\ResetPassword\Exception\ExpiredResetPasswordTokenException;
use SymfonyCasts\Bundle\ResetPassword\Exception\InvalidResetPasswordTokenException;
use SymfonyCasts\Bundle\ResetPassword\Exception\TooManyPasswordRequestsException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\ExpiredSignatureException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\InvalidSignatureException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\WrongEmailVerifyException;

/**
 * Unit test for {@link UserExceptionService} class.
 */
class UserExceptionServiceTest extends TestCase
{
    private ?Request $request = null;
    private ?UserExceptionService $service = null;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->service = new UserExceptionService();

        $storage = new MockArraySessionStorage();
        $session = new Session($storage);
        $this->request = new Request();
        $this->request->setSession($session);
    }

    public function getExceptions(): array
    {
        return [
            // register user
            [new ExpiredSignatureException(), 'registration_expired_signature'],
            [new InvalidSignatureException(), 'registration_invalid_signature'],
            [new WrongEmailVerifyException(), 'registration_wrong_email_verify'],

            // reset password
            [new ExpiredResetPasswordTokenException(), 'reset_expired_reset_password_token'],
            [new InvalidResetPasswordTokenException(), 'reset_invalid_reset_password_token'],
            [new TooManyPasswordRequestsException(new \DateTime('2000-01-01')), 'reset_too_many_password_request', 1],

            // mailer
            [new TransportException(), 'send_email_error'],

            // other
            [new \Exception(), 'error_unknown'],
        ];
    }

    /**
     *  @dataProvider getExceptions
     */
    public function testException(\Throwable $e, string $message, int $messageData = 0): void
    {
        $result = $this->mapException($e);
        $this->assertEquals(0, $result->getCode());
        $this->assertEquals($message, $result->getMessage());
        $this->assertEquals($message, $result->getMessageKey());
        $this->assertCount($messageData, $result->getMessageData());
        $this->assertInstanceOf($e::class, $result->getPrevious());
    }

    private function mapException(\Throwable $e): CustomUserMessageAuthenticationException
    {
        $this->assertNotNull($this->request);
        $this->assertNotNull($this->service);
        $this->service->handleException($this->request, $e);
        $result = $this->request->getSession()->get(Security::AUTHENTICATION_ERROR);
        $this->assertInstanceOf(CustomUserMessageAuthenticationException::class, $result);

        return $result;
    }
}
