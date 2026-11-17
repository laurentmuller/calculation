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
use App\Enums\Theme;
use App\Listener\LoginListener;
use App\Repository\UserRepository;
use App\Service\ThemeService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Translation\IdentityTranslator;

final class LoginListenerTest extends TestCase
{
    public function testLogin(): void
    {
        $user = $this->createUser();
        $event = $this->createEvent($user);
        $listener = $this->createListener();
        $listener->onLoginSuccess($event);
        self::assertNotNull($user->lastLogin);
    }

    private function createEvent(User $user): LoginSuccessEvent
    {
        $event = self::createMock(LoginSuccessEvent::class);
        $event->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $event->expects(self::once())
            ->method('getRequest')
            ->willReturn(new Request());
        $event->expects(self::once())
            ->method('getResponse')
            ->willReturn(new Response());

        return $event;
    }

    private function createListener(): LoginListener
    {
        $repository = $this->createRepository();
        $service = $this->createService();

        return new LoginListener($repository, $service)
            ->setRequestStack($this->createRequestStack())
            ->setTranslator(new IdentityTranslator());
    }

    private function createRepository(): UserRepository
    {
        $repository = self::createMock(UserRepository::class);
        $repository->expects(self::once())
            ->method('flush');

        return $repository;
    }

    private function createRequestStack(): RequestStack
    {
        $session = self::createStub(SessionInterface::class);
        $requestStack = self::createMock(RequestStack::class);
        $requestStack->expects(self::once())
            ->method('getSession')
            ->willReturn($session);

        return $requestStack;
    }

    private function createService(): ThemeService
    {
        $service = self::createMock(ThemeService::class);
        $service->expects(self::once())
            ->method('getTheme')
            ->willReturn(Theme::LIGHT);
        $service->expects(self::once())
            ->method('saveTheme');

        return $service;
    }

    private function createUser(): User
    {
        return new User()
            ->withUsername('user_name');
    }
}
