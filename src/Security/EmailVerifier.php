<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Security;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 * Email verifier.
 *
 * @author Laurent Muller
 */
class EmailVerifier
{
    private $helper;
    private $mailer;
    private $manager;

    public function __construct(VerifyEmailHelperInterface $helper, MailerInterface $mailer, EntityManagerInterface $manager)
    {
        $this->helper = $helper;
        $this->mailer = $mailer;
        $this->manager = $manager;
    }

    /**
     * @throws VerifyEmailExceptionInterface
     */
    public function handleEmailConfirmation(Request $request, UserInterface $user): void
    {
        $this->helper->validateEmailConfirmation($request->getUri(), (string) $user->getId(), $user->getEmail());

        $user->setVerified(true);

        $this->manager->persist($user);
        $this->manager->flush();
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendEmailConfirmation(string $verifyEmailRouteName, UserInterface $user, TemplatedEmail $email): void
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
