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
use App\Mime\ResetPasswordEmail;
use App\Repository\UserRepository;
use App\Service\UserExceptionService;
use App\Traits\FooterTextTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * Controller to reset the user password.
 */
#[AsController]
#[Route(path: '/reset-password')]
#[IsGranted(AuthenticatedVoter::PUBLIC_ACCESS)]
class ResetPasswordController extends AbstractController
{
    use FooterTextTrait;
    use ResetPasswordControllerTrait;

    private const ROUTE_CHECK = 'app_check_email';
    private const ROUTE_FORGET = 'app_forgot_password_request';
    private const ROUTE_RESET = 'app_reset_password';
    private const THROTTLE_MINUTES = 5;
    private const THROTTLE_OFFSET = 'PT3300S';

    /**
     * Constructor.
     */
    public function __construct(
        private readonly ResetPasswordHelperInterface $helper,
        private readonly UserRepository $repository,
        private readonly UserExceptionService $service
    ) {
    }

    /**
     * Confirmation page after a user has requested a password reset.
     */
    #[Route(path: '/check-email', name: self::ROUTE_CHECK)]
    public function checkEmail(): Response
    {
        // prevent users from directly accessing this page
        // generates a fake token if the user does not exist or
        // someone hit this page directly.
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->helper->generateFakeResetToken();
        }

        return $this->renderForm('reset_password/check_email.html.twig', [
            'expires_date' => $resetToken->getExpiresAt(),
            'expires_life_time' => $this->getExpiresLifeTime($resetToken),
            'throttle_date' => $this->getThrottleAt($resetToken),
            'throttle_life_time' => $this->getThrottleLifeTime(),
        ]);
    }

    /**
     * Display and process form to request a password reset.
     */
    #[Route(path: '', name: self::ROUTE_FORGET)]
    public function request(Request $request, MailerInterface $mailer, AuthenticationUtils $utils): Response
    {
        $form = $this->createForm(RequestChangePasswordType::class);
        if ($this->handleRequestForm($request, $form)) {
            $usernameOrEmail = (string) $form->get('user')->getData();

            return $this->sendEmail($request, $usernameOrEmail, $mailer);
        }

        return $this->renderForm('reset_password/request.html.twig', [
            'error' => $utils->getLastAuthenticationError(),
            'form' => $form,
        ]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     */
    #[Route(path: '/reset/{token}', name: self::ROUTE_RESET)]
    public function reset(Request $request, UserPasswordHasherInterface $hasher, ?string $token = null): Response
    {
        if ($token) {
            // we store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute(self::ROUTE_RESET);
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createNotFoundException($this->trans('reset_not_found_password_token', [], 'security'));
        }

        try {
            /** @var User $user */
            $user = $this->helper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->service->handleException($request, $e);

            return $this->redirectToRoute(self::ROUTE_FORGET);
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
            $this->infoTrans('resetting.success', ['%username%' => $user->getUserIdentifier()]);

            // redirect
            return $this->redirectToHomePage();
        }

        return $this->renderForm('reset_password/reset.html.twig', [
            'form' => $form,
        ]);
    }

    private function createEmail(User $user, ResetPasswordToken $resetToken): ResetPasswordEmail
    {
        $email = new ResetPasswordEmail($this->getTranslator());
        $email->to($user->getAddress())
            ->from($this->getAddressFrom())
            ->setFooterText($this->getFooterValue())
            ->subject($this->trans('resetting.request.title'))
            ->action($this->trans('resetting.request.submit'), $this->getResetAction($resetToken))
            ->context([
                'token' => $resetToken->getToken(),
                'username' => $user->getUserIdentifier(),
                'expires_date' => $resetToken->getExpiresAt(),
                'expires_life_time' => $this->getExpiresLifeTime($resetToken),
                'throttle_date' => $this->getThrottleAt($resetToken),
                'throttle_life_time' => $this->getThrottleLifeTime(),
            ]);

        return $email;
    }

    private function getExpiresLifeTime(ResetPasswordToken $resetToken): string
    {
        return $this->trans(
            $resetToken->getExpirationMessageKey(),
            $resetToken->getExpirationMessageData(),
            'ResetPasswordBundle'
        );
    }

    private function getFooterValue(): string
    {
        $appName = $this->getParameterString('app_name');
        $appVersion = $this->getParameterString('app_version');

        return $this->getFooterText($appName, $appVersion);
    }

    private function getResetAction(ResetPasswordToken $resetToken): string
    {
        return $this->generateUrl(self::ROUTE_RESET, ['token' => $resetToken->getToken()], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function getThrottleAt(ResetPasswordToken $resetToken): \DateTimeInterface
    {
        /** @var \DateTime|\DateTimeImmutable $expireAt */
        $expireAt = clone $resetToken->getExpiresAt();

        return $expireAt->sub(new \DateInterval(self::THROTTLE_OFFSET));
    }

    private function getThrottleLifeTime(): string
    {
        return $this->trans('%count% minute|%count% minutes', ['%count%' => self::THROTTLE_MINUTES], 'ResetPasswordBundle');
    }

    /**
     * Send email to the user for resetting the password.
     */
    private function sendEmail(Request $request, string $usernameOrEmail, MailerInterface $mailer): RedirectResponse
    {
        // do not reveal whether a user account was found or not.
        $user = $this->repository->findByUsernameOrEmail($usernameOrEmail);
        if (!$user instanceof User) {
            return $this->redirectToRoute(self::ROUTE_CHECK);
        }

        try {
            $resetToken = $this->helper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->service->handleException($request, $e);

            return $this->redirectToRoute(self::ROUTE_FORGET);
        }

        try {
            $notification = $this->createEmail($user, $resetToken);
            $mailer->send($notification);
            $this->setTokenObjectInSession($resetToken);
        } catch (TransportExceptionInterface $e) {
            $this->helper->removeResetRequest($resetToken->getToken());
            $this->service->handleException($request, $e);

            return $this->redirectToRoute(self::ROUTE_FORGET);
        }

        return $this->redirectToRoute(self::ROUTE_CHECK);
    }
}
