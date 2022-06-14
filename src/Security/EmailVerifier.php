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

namespace App\Security;

use App\Entity\User;
use App\Mime\RegistrationEmail;
use App\Traits\TranslatorTrait;
use Doctrine\ORM\EntityManagerInterface;
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
class EmailVerifier
{
    use TranslatorTrait;

    /**
     * Constructor.
     */
    public function __construct(
        TranslatorInterface $translator,
        private readonly VerifyEmailHelperInterface $helper,
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $manager
    ) {
        $this->translator = $translator;
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
        $this->manager->persist($user);
        $this->manager->flush();
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

    /**
     * Generate signature.
     */
    private function generateSignature(string $routeName, User $user): VerifyEmailSignatureComponents
    {
        $userId = (string) $user->getId();
        $userEmail = (string) $user->getEmail();
        $parameters = ['id' => $userId];

        return $this->helper->generateSignature($routeName, $userId, $userEmail, $parameters);
    }

    private function getExpiresLifeTime(VerifyEmailSignatureComponents $signature): string
    {
        return $this->trans(
            $signature->getExpirationMessageKey(),
            $signature->getExpirationMessageData(),
            'VerifyEmailBundle'
        );
    }

    /**
     * Validate email confirmation.
     *
     * @throws VerifyEmailExceptionInterface
     */
    private function validateEmail(Request $request, User $user): void
    {
        $signedUrl = $request->getUri();
        $userId = (string) $user->getId();
        $userEmail = (string) $user->getEmail();
        $this->helper->validateEmailConfirmation($signedUrl, $userId, $userEmail);
    }
}
