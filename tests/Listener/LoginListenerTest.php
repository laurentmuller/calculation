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
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[CoversClass(LoginListener::class)]
class LoginListenerTest extends TestCase
{
    use TranslatorMockTrait;

    /**
     * @throws Exception
     */
    public function testLogin(): void
    {
        $user = new User();
        $user->setUsername('user_name');
        $event = $this->createLoginSuccessEvent($user);
        $listener = $this->createListener();
        self::assertNull($user->getLastLogin());
        $listener->onLoginSuccess($event);
        self::assertNotNull($user->getLastLogin());
    }

    /**
     * @throws Exception
     */
    private function createListener(): LoginListener
    {
        $repository = $this->createMock(UserRepository::class);
        $listener = new LoginListener($repository, 'Calculation');
        $listener->setTranslator($this->createTranslator());
        $listener->setRequestStack($this->createRequestStack());

        return $listener;
    }

    /**
     * @throws Exception
     */
    private function createLoginSuccessEvent(User $user): MockObject&LoginSuccessEvent
    {
        $event = $this->createMock(LoginSuccessEvent::class);
        $event->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        return $event;
    }

    /**
     * @throws Exception
     */
    private function createRequestStack(): MockObject&RequestStack
    {
        $session = $this->createMock(SessionInterface::class);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects(self::once())
            ->method('getSession')
            ->willReturn($session);

        return $requestStack;
    }
}
