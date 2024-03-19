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

use App\Entity\User;
use App\Mime\RegistrationEmail;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\Model\VerifyEmailSignatureComponents;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 * Email verifier used for register new user.
 */
readonly class EmailVerifier
{
    public function __construct(
        private VerifyEmailHelperInterface $helper,
        private MailerInterface $mailer,
        private UserRepository $repository,
        private TranslatorInterface $translator
    ) {
    }

    /**
     * Handle email confirmation.
     *
     * @throws VerifyEmailExceptionInterface
     */
    public function handleEmail(Request $request, User $user): void
    {
        $this->validateEmail($request, $user);
        $user->setVerified(true);
        $this->repository->persist($user);
    }

    /**
     * Sends an email of confirmation.
     *
     * @throws TransportExceptionInterface
     */
    public function sendEmail(string $routeName, User $user, RegistrationEmail $email): void
    {
        $signature = $this->generateSignature($routeName, $user);
        $email->action($this->trans('registration.action'), $signature->getSignedUrl());
        $context = $email->getContext();
        $context['username'] = $user->getUserIdentifier();
        $context['expires_date'] = $signature->getExpiresAt();
        $context['expires_life_time'] = $this->getExpiresLifeTime($signature);
        $email->context($context);
        $this->mailer->send($email);
    }

    private function generateSignature(string $routeName, User $user): VerifyEmailSignatureComponents
    {
        $id = (string) $user->getId();
        $email = (string) $user->getEmail();
        $parameters = ['id' => $id];

        return $this->helper->generateSignature($routeName, $id, $email, $parameters);
    }

    private function getExpiresLifeTime(VerifyEmailSignatureComponents $signature): string
    {
        return $this->trans(
            $signature->getExpirationMessageKey(),
            $signature->getExpirationMessageData(),
            'VerifyEmailBundle'
        );
    }

    private function trans(string $id, array $parameters = [], ?string $domain = null): string
    {
        return $this->translator->trans($id, $parameters, $domain);
    }

    /**
     * @throws VerifyEmailExceptionInterface
     */
    private function validateEmail(Request $request, User $user): void
    {
        $id = (string) $user->getId();
        $email = (string) $user->getEmail();
        $this->helper->validateEmailConfirmationFromRequest($request, $id, $email);
    }
}
