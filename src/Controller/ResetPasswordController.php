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

namespace App\Controller;

use App\Form\User\RequestChangePasswordType;
use App\Form\User\ResetChangePasswordType;
use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ExpiredResetPasswordTokenException;
use SymfonyCasts\Bundle\ResetPassword\Exception\InvalidResetPasswordTokenException;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\TooManyPasswordRequestsException;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * Controller to reset the user password.
 *
 * @author Laurent Muller
 *
 * @Route("/reset-password")
 */
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;
    use TargetPathTrait;

    private ResetPasswordHelperInterface $helper;

    private UserRepository $repository;

    public function __construct(ResetPasswordHelperInterface $helper, UserRepository $repository)
    {
        $this->helper = $helper;
        $this->repository = $repository;
    }

    /**
     * Confirmation page after a user has requested a password reset.
     *
     * @Route("/check-email", name="app_check_email")
     */
    public function checkEmail(): Response
    {
        // We prevent users from directly accessing this page
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            return $this->redirectToRoute('app_forgot_password_request');
        }

        return $this->renderForm('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    /**
     * Display and process form to request a password reset.
     *
     * @Route("", name="app_forgot_password_request")
     */
    public function request(Request $request, MailerInterface $mailer, AuthenticationUtils $utils): Response
    {
        $form = $this->createForm(RequestChangePasswordType::class);
        if ($this->handleRequestForm($request, $form)) {
            $usernameOrEmail = $form->get('username')->getData();

            return $this->sendPasswordResetEmail($request, $usernameOrEmail, $mailer);
        }

        return $this->renderForm('reset_password/request.html.twig', [
            'error' => $utils->getLastAuthenticationError(),
            'form' => $form,
        ]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     *
     * @Route("/reset/{token}", name="app_reset_password")
     */
    public function reset(Request $request, UserPasswordHasherInterface $hasher, ?string $token = null): Response
    {
        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createNotFoundException($this->trans('resetting.not_found_password_token'));
        }

        try {
            /** @var \App\Entity\User $user */
            $user = $this->helper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            if ($request->hasSession()) {
                $exception = $this->handleResetException($e);
                $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
            }

            return $this->redirectToRoute('app_forgot_password_request');
        }

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ResetChangePasswordType::class, $user);
        if ($this->handleRequestForm($request, $form)) {
            // A password reset token should be used only once, remove it.
            $this->helper->removeResetRequest($token);

            // Encode the plain password, and set it.
            $plainPassword = $form->get('plainPassword')->getData();
            $encodedPassword = $hasher->hashPassword($user, $plainPassword);
            $user->setPassword($encodedPassword);
            $this->getManager()->flush();

            // The session is cleaned up after the password has been changed.
            $this->cleanSessionAfterReset();

            // redirect
            return $this->createRedirectResponse($request);
        }

        return $this->renderForm('reset_password/reset.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Creates a redirect response.
     */
    private function createRedirectResponse(Request $request): Response
    {
        if ($request->hasSession() && $targetPath = $this->getTargetPath($request->getSession(), 'main')) {
            return new RedirectResponse($targetPath);
        }

        return $this->redirectToHomePage();
    }

    /**
     * Translate the given exception.
     */
    private function handleResetException(ResetPasswordExceptionInterface $e): CustomUserMessageAuthenticationException
    {
        if ($e instanceof ExpiredResetPasswordTokenException) {
            return new CustomUserMessageAuthenticationException('reset.expired_reset_password_token', [], 0, $e);
        }
        if ($e instanceof InvalidResetPasswordTokenException) {
            return new CustomUserMessageAuthenticationException('reset.invalid_reset_password_token', [], 0, $e);
        }
        if ($e instanceof TooManyPasswordRequestsException) {
            $data = ['%availableAt%' => $e->getAvailableAt()->format('H:i')];

            return new CustomUserMessageAuthenticationException('reset.too_many_password_request', $data, 0, $e);
        }

        return new CustomUserMessageAuthenticationException($e->getReason(), [], 0, $e);
    }

    /**
     * Send an email to the user for resetting the password.
     */
    private function sendPasswordResetEmail(Request $request, string $usernameOrEmail, MailerInterface $mailer): RedirectResponse
    {
        $user = $this->repository->findByUsernameOrEmail($usernameOrEmail);

        // Do not reveal whether a user account was found or not.
        if (!$user) {
            return $this->redirectToRoute('app_check_email');
        }

        try {
            $resetToken = $this->helper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            // add session error
            if ($request->hasSession()) {
                $exception = $this->handleResetException($e);
                $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
            }

            return $this->redirectToRoute('app_forgot_password_request');
        }

        $subject = $this->trans('resetting.request.title');
        $email = (new TemplatedEmail())
            ->from($this->getAddressFrom())
            ->to($user->getAddress())
            ->subject($subject)
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'username' => $user->getUsername(),
                'resetToken' => $resetToken,
            ]);

        try {
            $mailer->send($email);
            $this->setTokenObjectInSession($resetToken);
        } catch (TransportException $e) {
            $this->helper->removeResetRequest($resetToken->getToken());
            if ($request->hasSession()) {
                $exception = new CustomUserMessageAuthenticationException('reset.send_email_error');
                $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
            }

            return $this->redirectToRoute('app_forgot_password_request');
        }

        return $this->redirectToRoute('app_check_email');
    }
}
