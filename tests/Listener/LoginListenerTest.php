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
use App\Listener\LoginListener;
use App\Repository\UserRepository;
use App\Tests\TranslatorStubTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class LoginListenerTest extends TestCase
{
    use TranslatorStubTrait;

    public function testLogin(): void
    {
        $user = $this->createUser();
        $event = $this->createEvent($user);
        $listener = $this->createListener();
        $listener->onLoginSuccess($event);
        self::assertNotNull($user->getLastLogin());
    }

    private function createEvent(User $user): LoginSuccessEvent
    {
        $event = $this->createMock(LoginSuccessEvent::class);
        $event->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        return $event;
    }

    private function createListener(): LoginListener
    {
        $repository = self::createStub(UserRepository::class);
        $listener = new LoginListener($repository);
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
