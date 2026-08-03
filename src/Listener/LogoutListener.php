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

namespace App\Listener;

use App\Entity\User;
use App\Service\ApplicationService;
use App\Traits\TranslatorFlashMessageAwareTrait;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class LogoutListener implements ServiceSubscriberInterface
{
    use ServiceMethodsSubscriberTrait;
    use TranslatorFlashMessageAwareTrait;

    #[AsEventListener]
    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();
        if ($user instanceof User) {
            $this->successTrans('security.logout.success', [
                '%user_name%' => $user->getUserIdentifier(),
                '%app_name%' => ApplicationService::APP_FULL_NAME,
            ]);
        }
    }
}
