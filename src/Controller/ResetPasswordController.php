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

use App\Attribute\ForPublicAccess;
use App\Attribute\GetPostRoute;
use App\Attribute\GetRoute;
use App\Attribute\IndexRoute;
use App\Entity\User;
use App\Form\User\RequestChangePasswordType;
use App\Form\User\ResetChangePasswordType;
use App\Security\LoginFormAuthenticator;
use App\Service\ResetPasswordService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * Controller to reset the user password.
 */
#[ForPublicAccess]
#[Route(path: '/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    /**
     * The reset password route name.
     */
    public const ROUTE_RESET = 'app_reset_password';

    private const ROUTE_CHECK = 'app_check_email';
    private const ROUTE_REQUEST = 'app_forgot_password_request';

    public function __construct(
        private readonly ResetPasswordHelperInterface $helper,
        private readonly ResetPasswordService $service,
    ) {
    }

    /**
     * Confirmation page after a user has requested a password reset.
     */
    #[GetRoute(path: '/check-email', name: self::ROUTE_CHECK)]
    public function checkEmail(): Response
    {
        $token = $this->getTokenObjectFromSession() ?? $this->service->generateFakeResetToken();

        return $this->render('reset_password/check_email.html.twig', [
            'expires_date' => $this->getExpiresAt($token),
            'expires_life_time' => $this->service->getExpiresLifeTime($token),
            'throttle_date' => $this->service->getThrottleAt($token),
            'throttle_life_time' => $this->service->getThrottleLifeTime(),
        ]);
    }

    /**
     * Display and process the form to request a password reset.
     */
    #[GetPostRoute(path: IndexRoute::PATH, name: self::ROUTE_REQUEST)]
    public function request(Request $request, AuthenticationUtils $utils): Response
    {
        $form = $this->createForm(RequestChangePasswordType::class);
        if ($this->handleRequestForm($request, $form)) {
            $user = (string) $form->get('user')->getData();

            return $this->sendEmail($request, $user);
        }

        return $this->render('reset_password/request.html.twig', [
            'error' => $utils->getLastAuthenticationError(),
            'form' => $form,
        ]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     */
    #[GetPostRoute(path: '/reset/{token}', name: self::ROUTE_RESET)]
    public function reset(Request $request, Security $security, ?string $token = null): Response
    {
        if (null !== $token) {
            $this->storeTokenInSession($token);

            return $this->redirectToRoute(self::ROUTE_RESET);
        }
        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createTranslatedNotFoundException('reset.not_found_password_token', domain: 'security');
        }

        try {
            /** @var User $user */
            $user = $this->helper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->service->handleException($request, $e);

            return $this->redirectToRoute(self::ROUTE_REQUEST);
        }

        $form = $this->createForm(ResetChangePasswordType::class, $user);
        if ($this->handleRequestForm($request, $form)) {
            $this->helper->removeResetRequest($token);
            $this->service->flush();
            $this->cleanSessionAfterReset();

            return $this->redirectAfterReset($security, $user);
        }

        return $this->render('reset_password/reset.html.twig', ['form' => $form]);
    }

    private function getExpiresAt(ResetPasswordToken $token): DatePoint
    {
        return DatePoint::createFromInterface($token->getExpiresAt());
    }

    private function redirectAfterReset(Security $security, User $user): Response
    {
        try {
            $response = $security->login($user, LoginFormAuthenticator::class);
            if ($response instanceof Response) {
                return $response;
            }
        } catch (\Throwable) {
            // ignore
        }

        return $this->redirectToHomePage();
    }

    private function sendEmail(Request $request, string $user): RedirectResponse
    {
        $result = $this->service->sendEmail($request, $user);
        if (false === $result) {
            return $this->redirectToRoute(self::ROUTE_CHECK);
        }
        if (!$result instanceof ResetPasswordToken) {
            return $this->redirectToRoute(self::ROUTE_REQUEST);
        }
        $this->setTokenObjectInSession($result);

        return $this->redirectToRoute(self::ROUTE_CHECK);
    }
}
