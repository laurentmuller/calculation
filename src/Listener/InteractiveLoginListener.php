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

    private string $appNameVersion;

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
        $this->updateUser($token->getUser());
        $this->notify($event->getRequest(), $token->getUsername());
    }

    /**
     * Notify the success login to the user.
     *
     * @param string|null $username the logged user name
     */
    private function notify(Request $request, ?string $username): void
    {
        if ($username && $request->hasSession()) {
            $this->session = $request->getSession();
            $params = [
                '%username%' => $username,
                '%appname%' => $this->appNameVersion,
            ];
            $this->succesTrans('security.login.success', $params);
        }
    }

    /**
     * Update the last login date, if applicable, of the given user.
     *
     * @param mixed $user the logged user
     */
    private function updateUser($user): void
    {
        if ($user instanceof User) {
            $this->repository->updateLastLogin($user);
        }
    }
}
