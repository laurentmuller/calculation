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

namespace App\Tests\Service;

use App\Service\UserExceptionService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use SymfonyCasts\Bundle\ResetPassword\Exception\ExpiredResetPasswordTokenException;
use SymfonyCasts\Bundle\ResetPassword\Exception\InvalidResetPasswordTokenException;
use SymfonyCasts\Bundle\ResetPassword\Exception\TooManyPasswordRequestsException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\ExpiredSignatureException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\InvalidSignatureException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\WrongEmailVerifyException;

/**
 * Unit test for {@link App\Service\UserExceptionService} class.
 *
 * @author Laurent Muller
 */
class UserExceptionServiceTest extends TestCase
{
    private ?UserExceptionService $service = null;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->service = new UserExceptionService();
    }

    public function getExceptions(): array
    {
        return [
            // register user
            [new ExpiredSignatureException()],
            [new InvalidSignatureException()],
            [new WrongEmailVerifyException()],

            // reset password
            [new ExpiredResetPasswordTokenException()],
            [new InvalidResetPasswordTokenException()],
            [new TooManyPasswordRequestsException(new \DateTime('2000-01-01')), 1],

            // mailer
            [new TransportException()],

            // other
            [new \Exception()],
        ];
    }

    public function getExceptionWithMessages(): array
    {
        return [
            // register user
            [new ExpiredSignatureException(), 'registration_expired_signature'],
            [new InvalidSignatureException(), 'registration_invalid_signature'],
            [new WrongEmailVerifyException(), 'registration_wrong_email_verify'],

            // reset password
            [new ExpiredResetPasswordTokenException(), 'reset_expired_reset_password_token'],
            [new InvalidResetPasswordTokenException(), 'reset_invalid_reset_password_token'],
            [new TooManyPasswordRequestsException(new \DateTime('2000-01-01')), 'reset_too_many_password_request'],

            // mailer
            [new TransportException(), 'send_email_error'],

            // other
            [new \Exception(), 'error_unknown'],
        ];
    }

    /**
     *  @dataProvider getExceptions
     */
    public function testCode(\Throwable $e): void
    {
        $result = $this->mapException($e);
        $this->assertEquals(0, $result->getCode());
    }

    /**
     *  @dataProvider getExceptions
     */
    public function testInstancePrevious(\Throwable $e): void
    {
        $result = $this->mapException($e);
        $this->assertInstanceOf(\get_class($e), $result->getPrevious());
    }

    /**
     *  @dataProvider getExceptions
     */
    public function testInstanceResult(\Throwable $e): void
    {
        $result = $this->mapException($e);
        $this->assertInstanceOf(CustomUserMessageAuthenticationException::class, $result);
    }

    /**
     *  @dataProvider getExceptionWithMessages
     */
    public function testMessage(\Throwable $e, string $message): void
    {
        $result = $this->mapException($e);
        $this->assertEquals($message, $result->getMessage());
    }

    /**
     *  @dataProvider getExceptions
     */
    public function testMessageData(\Throwable $e, int $count = 0): void
    {
        $result = $this->mapException($e);
        $this->assertEquals($count, \count($result->getMessageData()));
    }

    /**
     *  @dataProvider getExceptionWithMessages
     */
    public function testMessageKey(\Throwable $e, string $message): void
    {
        $result = $this->mapException($e);
        $this->assertEquals($message, $result->getMessageKey());
    }

    private function mapException(\Throwable $e): CustomUserMessageAuthenticationException
    {
        return $this->service->mapException($e);
    }
}
