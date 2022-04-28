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

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use SymfonyCasts\Bundle\ResetPassword\Exception\ExpiredResetPasswordTokenException;
use SymfonyCasts\Bundle\ResetPassword\Exception\InvalidResetPasswordTokenException;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\TooManyPasswordRequestsException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\ExpiredSignatureException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\InvalidSignatureException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\WrongEmailVerifyException;

/**
 * Service to map registration and reset password exceptions.
 */
class UserExceptionService
{
    /**
     * Creates a custom user exception.
     */
    public function createException(string $message, \Throwable $previous = null, array $parameters = [], int $code = 0): CustomUserMessageAuthenticationException
    {
        return new CustomUserMessageAuthenticationException($message, $parameters, $code, $previous);
    }

    /**
     * Handle an exception by set the authentication error to the session.
     */
    public function handleException(Request $request, \Throwable $e): void
    {
        if ($request->hasSession()) {
            $exception = $this->mapException($e);
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        }
    }

    /**
     * Map the given exception to a custom user exception.
     */
    public function mapException(\Throwable $e, array $parameters = [], int $code = 0): CustomUserMessageAuthenticationException
    {
        // register user
        if ($e instanceof ExpiredSignatureException) {
            return $this->createException('registration_expired_signature', $e, $parameters, $code);
        }
        if ($e instanceof InvalidSignatureException) {
            return $this->createException('registration_invalid_signature', $e, $parameters, $code);
        }
        if ($e instanceof WrongEmailVerifyException) {
            return $this->createException('registration_wrong_email_verify', $e, $parameters, $code);
        }
        if ($e instanceof VerifyEmailExceptionInterface) {
            return $this->createException($e->getReason(), $e, $parameters, $code);
        }

        // reset password
        if ($e instanceof ExpiredResetPasswordTokenException) {
            return $this->createException('reset_expired_reset_password_token', $e, $parameters, $code);
        }
        if ($e instanceof InvalidResetPasswordTokenException) {
            return $this->createException('reset_invalid_reset_password_token', $e, $parameters, $code);
        }
        if ($e instanceof TooManyPasswordRequestsException) {
            $parameters['%availableAt%'] = $e->getAvailableAt()->format('H:i');

            return $this->createException('reset_too_many_password_request', $e, $parameters, $code);
        }
        if ($e instanceof ResetPasswordExceptionInterface) {
            return $this->createException($e->getReason(), $e, $parameters, $code);
        }

        // mailer
        if ($e instanceof TransportException) {
            return $this->createException('send_email_error', $e, $parameters, $code);
        }

        // default
        return $this->createException('error_unknown', $e, $parameters, $code);
    }
}
