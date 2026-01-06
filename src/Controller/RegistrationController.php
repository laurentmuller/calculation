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

use App\Attribute\GetPostRoute;
use App\Attribute\GetRoute;
use App\Attribute\IndexRoute;
use App\Entity\User;
use App\Enums\Importance;
use App\Form\User\UserRegistrationType;
use App\Mime\NotificationEmail;
use App\Model\TranslatableFlashMessage;
use App\Repository\UserRepository;
use App\Service\EmailVerifier;
use App\Service\UserExceptionService;
use App\Traits\LoggerTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * Controller to register a new user.
 */
#[Route(path: '/register')]
class RegistrationController extends AbstractController
{
    use LoggerTrait;

    private const ROUTE_REGISTER = 'user_register';
    private const ROUTE_VERIFY = 'user_verify';

    public function __construct(
        private readonly EmailVerifier $verifier,
        private readonly UserRepository $repository,
        private readonly UserExceptionService $service,
        private readonly LoggerInterface $logger
    ) {
    }

    #[\Override]
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Display and process form to register a new user.
     */
    #[GetPostRoute(path: IndexRoute::PATH, name: self::ROUTE_REGISTER)]
    public function register(Request $request, AuthenticationUtils $utils): Response
    {
        $user = new User();
        $form = $this->createForm(UserRegistrationType::class, $user)
            ->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->repository->persist($user);

            try {
                $email = $this->createEmail($user);
                $this->verifier->sendEmail(self::ROUTE_VERIFY, $user, $email);

                return $this->redirectToHomePage();
            } catch (TransportExceptionInterface $e) {
                $this->handleException($request, $e);

                return $this->redirectToRoute(self::ROUTE_REGISTER);
            }
        }

        return $this->render('registration/register.html.twig', [
            'error' => $utils->getLastAuthenticationError(),
            'form' => $form,
        ]);
    }

    /**
     * Verify the user e-mail.
     */
    #[GetRoute(path: '/verify', name: self::ROUTE_VERIFY)]
    public function verify(Request $request): RedirectResponse
    {
        $user = $this->findUser($request);
        if (!$user instanceof User) {
            return $this->redirectToRoute(self::ROUTE_REGISTER);
        }

        try {
            $this->verifier->handleEmail($request, $user);
        } catch (\Throwable $e) {
            $this->handleException($request, $e);

            return $this->redirectToRoute(self::ROUTE_REGISTER);
        }

        return $this->redirectToHomePage(
            message: new TranslatableFlashMessage(
                message: 'registration.confirmed',
                parameters: ['%username%' => $user],
            )
        );
    }

    private function createEmail(User $user): NotificationEmail
    {
        return NotificationEmail::instance($this->getTranslator(), 'notification/registration.html.twig')
            ->subject(new TranslatableMessage('registration.subject'))
            ->importance(Importance::MEDIUM)
            ->from($this->getAddressFrom())
            ->to($user->getAddress());
    }

    private function findUser(Request $request): ?User
    {
        $id = $this->getRequestInt($request, 'id');

        return 0 !== $id ? $this->repository->find($id) : null;
    }

    private function handleException(Request $request, \Throwable $e): void
    {
        $exception = $this->service->handleException($request, $e);
        $message = $this->service->translate($exception);
        $this->logException($exception, $message);
    }
}
