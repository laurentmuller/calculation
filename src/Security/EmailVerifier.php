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

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\Model\VerifyEmailSignatureComponents;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 * Email verifier used for register new user.
 *
 * @author Laurent Muller
 */
class EmailVerifier
{
    private VerifyEmailHelperInterface $helper;
    private MailerInterface $mailer;
    private EntityManagerInterface $manager;

    /**
     * Constructor.
     */
    public function __construct(VerifyEmailHelperInterface $helper, MailerInterface $mailer, EntityManagerInterface $manager)
    {
        $this->helper = $helper;
        $this->mailer = $mailer;
        $this->manager = $manager;
    }

    /**
     * Handle email confirmation.
     *
     * @throws VerifyEmailExceptionInterface
     */
    public function handleEmailConfirmation(Request $request, User $user): void
    {
        $this->validateEmailConfirmation($request, $user);
        $user->setVerified(true);
        $this->manager->persist($user);
        $this->manager->flush();
    }

    /**
     * Sends an email of confirmation.
     *
     * @throws TransportExceptionInterface
     */
    public function sendEmailConfirmation(string $verifyEmailRouteName, User $user, TemplatedEmail $email): void
    {
        $signature = $this->generateSignature($verifyEmailRouteName, $user);

        $context = [
            'username' => $user->getUsername(),
            'signedUrl' => $signature->getSignedUrl(),
            'expiresAtMessageKey' => $signature->getExpirationMessageKey(),
            'expiresAtMessageData' => $signature->getExpirationMessageData(),
        ];
        $email->context(\array_merge($email->getContext(), $context));

        $this->mailer->send($email);
    }

    /**
     * Generate signature.
     */
    private function generateSignature(string $routeName, User $user): VerifyEmailSignatureComponents
    {
        $userId = (string) $user->getId();
        $userEmail = $user->getEmail();
        $parameters = ['id' => $userId];

        return $this->helper->generateSignature($routeName, $userId, $userEmail, $parameters);
    }

    /**
     * Validate email confirmation.
     *
     * @throws VerifyEmailExceptionInterface
     */
    private function validateEmailConfirmation(Request $request, User $user): void
    {
        $signedUrl = $request->getUri();
        $userId = (string) $user->getId();
        $userEmail = $user->getEmail();

        $this->helper->validateEmailConfirmation($signedUrl, $userId, $userEmail);
    }
}
