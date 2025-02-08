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
use App\Listener\SwitchUserListener;
use App\Security\SecurityAttributes;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

class SwitchUserListenerTest extends TestCase
{
    use TranslatorMockTrait;

    public function testSwitchUserDefault(): void
    {
        $request = $this->createRequest();
        $token = $this->createUsernamePasswordToken();
        /** @psalm-var User $user */
        $user = $token->getUser();
        $event = new SwitchUserEvent($request, $user);

        $listener = $this->createListener();
        $listener->onSwitchUser($event);
        self::assertTrue($request->query->has('_switch_user'));
        self::assertSame('', $request->query->get('_switch_user'));
    }

    public function testSwitchUserExit(): void
    {
        $request = $this->createRequest('_exit');
        $token = $this->createSwitchUserToken();
        /** @psalm-var User $user */
        $user = $token->getUser();
        $event = new SwitchUserEvent($request, $user, $token);

        $listener = $this->createListener();
        $listener->onSwitchUser($event);
        self::assertTrue($request->query->has('_switch_user'));
        self::assertSame('_exit', $request->query->get('_switch_user'));
    }

    public function testSwitchUserOriginal(): void
    {
        $request = $this->createRequest('fake');
        $token = $this->createSwitchUserToken();
        /** @psalm-var User $user */
        $user = $token->getUser();
        $event = new SwitchUserEvent($request, $user, $token);

        $listener = $this->createListener();
        $listener->onSwitchUser($event);
        self::assertTrue($request->query->has('_switch_user'));
        self::assertSame('fake', $request->query->get('_switch_user'));
    }

    private function createListener(): SwitchUserListener
    {
        $listener = new SwitchUserListener();
        $listener->setTranslator($this->createMockTranslator());
        $listener->setRequestStack($this->createRequestStack());

        return $listener;
    }

    private function createRequest(string $action = ''): MockObject&Request
    {
        /** @psalm-var InputBag<string> $query */
        $query = new InputBag(['_switch_user' => $action]);
        $request = $this->createMock(Request::class);
        $request->query = $query;

        return $request;
    }

    private function createRequestStack(): MockObject&RequestStack
    {
        $session = $this->createMock(SessionInterface::class);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects(self::once())
            ->method('getSession')
            ->willReturn($session);

        return $requestStack;
    }

    private function createSwitchUserToken(): SwitchUserToken
    {
        $user = new User();
        $user->setUsername('target');
        $token = $this->createUsernamePasswordToken();

        return new SwitchUserToken($user, SecurityAttributes::MAIN_FIREWALL, [], $token);
    }

    private function createUsernamePasswordToken(): UsernamePasswordToken
    {
        $user = new User();
        $user->setUsername('source');

        return new UsernamePasswordToken($user, SecurityAttributes::MAIN_FIREWALL);
    }
}
