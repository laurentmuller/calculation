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

namespace App\Controller;

use App\Entity\User;
use App\Form\User\RequestChangePasswordType;
use App\Form\User\ResetChangePasswordType;
use App\Repository\UserRepository;
use App\Service\UserExceptionService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * Controller to reset the user password.
 */
#[Route(path: '/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;
    private const CHECK_ROUTE = 'app_check_email';
    private const FORGET_ROUTE = 'app_forgot_password_request';

    public function __construct(private readonly ResetPasswordHelperInterface $helper, private readonly UserRepository $repository)
    {
    }

    /**
     * Confirmation page after a user has requested a password reset.
     */
    #[Route(path: '/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        // Prevent users from directly accessing this page
        // Generates a fake token if the user does not exist or someone hit this page directly.
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->helper->generateFakeResetToken();
        }

        return $this->renderForm('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    /**
     * Display and process form to request a password reset.
     */
    #[Route(path: '', name: 'app_forgot_password_request')]
    public function request(Request $request, MailerInterface $mailer, UserExceptionService $service, AuthenticationUtils $utils): Response
    {
        $form = $this->createForm(RequestChangePasswordType::class);
        if ($this->handleRequestForm($request, $form)) {
            $usernameOrEmail = (string) $form->get('user')->getData();

            return $this->sendEmail($request, $usernameOrEmail, $mailer, $service);
        }

        return $this->renderForm('reset_password/request.html.twig', [
            'error' => $utils->getLastAuthenticationError(),
            'form' => $form,
        ]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     */
    #[Route(path: '/reset/{token}', name: 'app_reset_password')]
    public function reset(Request $request, UserPasswordHasherInterface $hasher, UserExceptionService $service, ?string $token = null): Response
    {
        if ($token) {
            // we store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }
        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createNotFoundException($this->trans('reset_not_found_password_token', [], 'security'));
        }

        try {
            /** @var User $user */
            $user = $this->helper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            if ($request->hasSession()) {
                $exception = $service->mapException($e);
                $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
            }

            return $this->redirectToRoute(self::FORGET_ROUTE);
        }
        // the token is valid; allow the user to change their password.
        $form = $this->createForm(ResetChangePasswordType::class, $user);
        if ($this->handleRequestForm($request, $form)) {
            // a password reset token should be used only once, remove it
            $this->helper->removeResetRequest($token);

            // encode password
            $plainPassword = (string) $form->get('plainPassword')->getData();
            $encodedPassword = $hasher->hashPassword($user, $plainPassword);
            $user->setPassword($encodedPassword);
            $this->repository->flush();

            // the session is cleaned up after the password has been changed.
            $this->cleanSessionAfterReset();

            // show message
            $this->infoTrans('resetting.success', ['%username%' => (string) $user]);

            // redirect
            return $this->redirectToHomePage();
        }

        return $this->renderForm('reset_password/reset.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Send email to the user for resetting the password.
     */
    private function sendEmail(Request $request, string $usernameOrEmail, MailerInterface $mailer, UserExceptionService $service): RedirectResponse
    {
        $user = $this->repository->findByUsernameOrEmail($usernameOrEmail);

        // Do not reveal whether a user account was found or not.
        if (!$user instanceof User) {
            return $this->redirectToRoute(self::CHECK_ROUTE);
        }

        try {
            $resetToken = $this->helper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            // add session error
            if ($request->hasSession()) {
                $exception = $service->mapException($e);
                $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
            }

            return $this->redirectToRoute(self::FORGET_ROUTE);
        }

        $subject = $this->trans('resetting.request.title');
        $email = (new TemplatedEmail())
            ->from($this->getAddressFrom())
            ->to($user->getAddress())
            ->subject($subject)
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'username' => $user->getUserIdentifier(),
                'resetToken' => $resetToken,
            ]);

        try {
            $mailer->send($email);
            $this->setTokenObjectInSession($resetToken);
        } catch (TransportExceptionInterface $e) {
            $this->helper->removeResetRequest($resetToken->getToken());
            if ($request->hasSession()) {
                $exception = $service->mapException($e);
                $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
            }

            return $this->redirectToRoute(self::FORGET_ROUTE);
        }

        return $this->redirectToRoute(self::CHECK_ROUTE);
    }
}
