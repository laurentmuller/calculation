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

use App\Constants\SecurityAttributes;
use App\Entity\User;
use App\Listener\SwitchUserListener;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\Firewall\SwitchUserListener as SecuritySwitchUserListener;

final class SwitchUserListenerTest extends TestCase
{
    use TranslatorMockTrait;

    private const string EXIT_VALUE = SecuritySwitchUserListener::EXIT_VALUE;
    private const string SWITCH_USER_PARAMETER = SwitchUserListener::SWITCH_USER_PARAMETER;

    public function testSwitchUserDefault(): void
    {
        $request = $this->createRequest();
        $token = $this->createUsernamePasswordToken();
        $user = $this->getUserToken($token);
        $event = new SwitchUserEvent($request, $user);

        $listener = $this->createListener();
        $listener->onSwitchUser($event);
        self::assertTrue($request->query->has(self::SWITCH_USER_PARAMETER));
        self::assertSame('', $request->query->get(self::SWITCH_USER_PARAMETER));
    }

    public function testSwitchUserExit(): void
    {
        $request = $this->createRequest(self::EXIT_VALUE);
        $token = $this->createSwitchUserToken();
        $user = $this->getUserToken($token);
        $event = new SwitchUserEvent($request, $user, $token);

        $listener = $this->createListener();
        $listener->onSwitchUser($event);
        self::assertTrue($request->query->has(self::SWITCH_USER_PARAMETER));
        self::assertSame(self::EXIT_VALUE, $request->query->get(self::SWITCH_USER_PARAMETER));
    }

    public function testSwitchUserOriginal(): void
    {
        $request = $this->createRequest('fake');
        $token = $this->createSwitchUserToken();
        $user = $this->getUserToken($token);
        $event = new SwitchUserEvent($request, $user, $token);

        $listener = $this->createListener();
        $listener->onSwitchUser($event);
        self::assertTrue($request->query->has(self::SWITCH_USER_PARAMETER));
        self::assertSame('fake', $request->query->get(self::SWITCH_USER_PARAMETER));
    }

    private function createListener(): SwitchUserListener
    {
        $listener = new SwitchUserListener();
        $listener->setTranslator($this->createMockTranslator())
            ->setRequestStack($this->createRequestStack());

        return $listener;
    }

    private function createRequest(string $action = ''): Request
    {
        /** @var InputBag<string> $query */
        $query = new InputBag([self::SWITCH_USER_PARAMETER => $action]);
        $request = $this->createMock(Request::class);
        $request->query = $query;

        return $request;
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

    private function createSwitchUserToken(): SwitchUserToken
    {
        $user = $this->createUser('target');

        return new SwitchUserToken(
            $user,
            SecurityAttributes::MAIN_FIREWALL,
            $user->getRoles(),
            $this->createUsernamePasswordToken()
        );
    }

    private function createUser(string $name): User
    {
        return (new User())->setUsername($name);
    }

    private function createUsernamePasswordToken(): UsernamePasswordToken
    {
        return new UsernamePasswordToken($this->createUser('source'), SecurityAttributes::MAIN_FIREWALL);
    }

    private function getUserToken(TokenInterface $token): UserInterface
    {
        /** @var UserInterface */
        return $token->getUser();
    }
}
