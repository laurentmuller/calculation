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
        $this->helper->validateEmailConfirmation($request->getUri(), (string) $user->getId(), $user->getEmail());

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
        $signature = $this->helper->generateSignature($verifyEmailRouteName, (string) $user->getId(), $user->getEmail());

        $context = $email->getContext();
        $context['signedUrl'] = $signature->getSignedUrl();
        $context['expiresAt'] = $signature->getExpiresAt();
        $context['username'] = $user->getUsername();
        $email->context($context);

        $this->mailer->send($email);
    }
}
