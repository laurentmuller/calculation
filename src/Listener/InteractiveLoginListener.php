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

namespace App\Listener;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Traits\TranslatorFlashMessageTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Listener for the user interactive login event.
 *
 * @author Laurent Muller
 */
class InteractiveLoginListener implements EventSubscriberInterface
{
    use TranslatorFlashMessageTrait;

    /**
     * The application name and version.
     *
     * @var string
     */
    private $appNameVersion;

    private UserRepository $repository;

    /**
     * Constructor.
     */
    public function __construct(UserRepository $repository, TranslatorInterface $translator, string $appNameVersion)
    {
        $this->repository = $repository;
        $this->translator = $translator;
        $this->appNameVersion = $appNameVersion;
    }

    public static function getSubscribedEvents()
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        ];
    }

    /**
     * Handles the interactive login event.
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $token = $event->getAuthenticationToken();
        if ($this->updateUser($token)) {
            $this->notify($event->getRequest(), $token);
        }
    }

    /**
     * Notify the success login to the user.
     *
     * @param Request        $request the request
     * @param TokenInterface $token   the token
     */
    private function notify(Request $request, TokenInterface $token): void
    {
        if ($request->hasSession()) {
            $this->session = $request->getSession();
            $params = [
                '%username%' => $token->getUsername(),
                '%appname%' => $this->appNameVersion,
            ];
            $this->succesTrans('security.login.success', $params);
        }
    }

    /**
     * Update the last login date, if applicable, of the user.
     *
     * @param TokenInterface $token the token to get user from
     *
     * @return bool true if updated
     */
    private function updateUser(TokenInterface $token): bool
    {
        if ($token instanceof RememberMeToken || $token instanceof PostAuthenticationGuardToken) {
            $user = $token->getUser();
            if ($user instanceof User) {
                return $this->repository->updateLastLogin($user);
            }
        }

        return false;
    }
}
