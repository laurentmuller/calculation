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

namespace App\Service;

use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
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
 *
 * @author Laurent Muller
 */
class UserExceptionService
{
    /**
     * Creeates a custom user exception.
     */
    public function createUserException(string $message, \Throwable $previous = null, array $parameters = [], int $code = 0): CustomUserMessageAuthenticationException
    {
        return new CustomUserMessageAuthenticationException($message, $parameters, $code, $previous);
    }

    /**
     * Map the given exception to a custom user exception.
     */
    public function mapException(\Throwable $e, array $parameters = [], int $code = 0): CustomUserMessageAuthenticationException
    {
        // register user
        if ($e instanceof ExpiredSignatureException) {
            return $this->createUserException('registration_expired_signature', $e, $parameters, $code);
        }
        if ($e instanceof InvalidSignatureException) {
            return $this->createUserException('registration_invalid_signature', $e, $parameters, $code);
        }
        if ($e instanceof WrongEmailVerifyException) {
            return $this->createUserException('registration_wrong_email_verify', $e, $parameters, $code);
        }
        if ($e instanceof VerifyEmailExceptionInterface) {
            return $this->createUserException($e->getReason(), $e, $parameters, $code);
        }

        // reset password
        if ($e instanceof ExpiredResetPasswordTokenException) {
            return $this->createUserException('reset_expired_reset_password_token', $e, $parameters, $code);
        }
        if ($e instanceof InvalidResetPasswordTokenException) {
            return $this->createUserException('reset_invalid_reset_password_token', $e, $parameters, $code);
        }
        if ($e instanceof TooManyPasswordRequestsException) {
            $parameters['%availableAt%'] = $e->getAvailableAt()->format('H:i');

            return $this->createUserException('reset_too_many_password_request', $e, $parameters, $code);
        }
        if ($e instanceof ResetPasswordExceptionInterface) {
            return $this->createUserException($e->getReason(), $e, $parameters, $code);
        }

        // mailer
        if ($e instanceof TransportException) {
            return $this->createUserException('send_email_error', $e, $parameters, $code);
        }

        //default
        return $this->createUserException('error_unknown', $e, $parameters, $code);
    }
}
