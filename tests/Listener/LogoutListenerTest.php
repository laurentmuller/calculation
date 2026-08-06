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

namespace App\Tests\Listener;

use App\Entity\User;
use App\Listener\LogoutListener;
use App\Tests\TranslatorStubTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final class LogoutListenerTest extends TestCase
{
    use TranslatorStubTrait;

    public function testLogin(): void
    {
        $user = $this->createUser();
        $event = $this->createEvent($user);
        $listener = $this->createListener();
        $listener->onLogout($event);
    }

    private function createEvent(User $user): LogoutEvent
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $event = $this->createMock(LogoutEvent::class);
        $event->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        return $event;
    }

    private function createListener(): LogoutListener
    {
        $listener = new LogoutListener();
        $listener->setTranslator($this->createStubTranslator());
        $listener->setRequestStack($this->createRequestStack());

        return $listener;
    }

    private function createRequestStack(): RequestStack
    {
        $session = self::createStub(SessionInterface::class);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects(self::once())
            ->method('getSession')
            ->willReturn($session);

        return $requestStack;
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setUsername('user_name');

        return $user;
    }
}
