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
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
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
     * Handle an exception by set the authentication error to the session.
     */
    public function handleException(Request $request, \Throwable $e): CustomUserMessageAuthenticationException
    {
        $exception = $this->mapException($e);
        if ($request->hasSession()) {
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        }

        return $exception;
    }

    /**
     * Creates a custom user exception.
     */
    private function createException(
        string $message,
        \Throwable $previous,
        array $parameters = []
    ): CustomUserMessageAuthenticationException {
        return new CustomUserMessageAuthenticationException($message, $parameters, 0, $previous);
    }

    /**
     * Map the given exception to a custom user exception.
     */
    private function mapException(\Throwable $e): CustomUserMessageAuthenticationException
    {
        $message = match (true) {
            // register user
            $e instanceof ExpiredSignatureException => 'registration_expired_signature',
            $e instanceof InvalidSignatureException => 'registration_invalid_signature',
            $e instanceof WrongEmailVerifyException => 'registration_wrong_email_verify',
            $e instanceof VerifyEmailExceptionInterface => $e->getReason(),
            // reset password
            $e instanceof ExpiredResetPasswordTokenException => 'reset_expired_reset_password_token',
            $e instanceof InvalidResetPasswordTokenException => 'reset_invalid_reset_password_token',
            $e instanceof TooManyPasswordRequestsException => 'reset_too_many_password_request',
            $e instanceof ResetPasswordExceptionInterface => $e->getReason(),
            // mailer
            $e instanceof TransportExceptionInterface => 'send_email_error',
            // other
            default => 'error_unknown'
        };

        $parameters = match (true) {
            $e instanceof TooManyPasswordRequestsException => ['%availableAt%' => $e->getAvailableAt()->format('H:i')],
            default => []
        };

        return $this->createException($message, $e, $parameters);
    }
}
