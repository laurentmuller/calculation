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
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ExpiredResetPasswordTokenException;
use SymfonyCasts\Bundle\ResetPassword\Exception\InvalidResetPasswordTokenException;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\TooManyPasswordRequestsException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\ExpiredSignatureException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\InvalidSignatureException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\WrongEmailVerifyException;

/**
 * Service to map registration and reset password exceptions.
 */
readonly class UserExceptionService
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    /**
     * Handle an exception by set the authentication error to the session (if any).
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
     * Translate the given exception, using the security domain.
     */
    public function translate(CustomUserMessageAuthenticationException $exception): string
    {
        return $this->translator->trans($exception->getMessageKey(), $exception->getMessageData(), 'security');
    }

    /**
     * Map the given exception to a custom user exception.
     */
    private function mapException(\Throwable $e): CustomUserMessageAuthenticationException
    {
        $message = match (true) {
            // register user
            $e instanceof ExpiredSignatureException => 'registration.expired_signature',
            $e instanceof InvalidSignatureException => 'registration.invalid_signature',
            $e instanceof WrongEmailVerifyException => 'registration.wrong_email_verify',
            // reset password
            $e instanceof ExpiredResetPasswordTokenException => 'reset.expired_reset_password_token',
            $e instanceof InvalidResetPasswordTokenException => 'reset.invalid_reset_password_token',
            $e instanceof TooManyPasswordRequestsException => 'reset.too_many_password_request',
            $e instanceof ResetPasswordExceptionInterface => $e->getReason(),
            // mailer
            $e instanceof TransportExceptionInterface => 'error.send_email',
            // other
            default => 'error.unknown'
        };

        $messageData = match (true) {
            $e instanceof TooManyPasswordRequestsException => ['%availableAt%' => $e->getAvailableAt()->format('H:i')],
            default => []
        };

        return new CustomUserMessageAuthenticationException(
            message: $message,
            messageData: $messageData,
            previous: $e
        );
    }
}
