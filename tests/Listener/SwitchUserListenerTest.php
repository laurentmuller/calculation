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

final class SwitchUserListenerTest extends TestCase
{
    use TranslatorMockTrait;

    public function testSwitchUserDefault(): void
    {
        $request = $this->createRequest();
        $token = $this->createUsernamePasswordToken();
        $user = $this->getUserToken($token);
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
        $user = $this->getUserToken($token);
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
        $user = $this->getUserToken($token);
        $event = new SwitchUserEvent($request, $user, $token);

        $listener = $this->createListener();
        $listener->onSwitchUser($event);
        self::assertTrue($request->query->has('_switch_user'));
        self::assertSame('fake', $request->query->get('_switch_user'));
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
        $query = new InputBag(['_switch_user' => $action]);
        $request = $this->createMock(Request::class);
        $request->query = $query;

        return $request;
    }

    private function createRequestStack(): RequestStack
    {
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects(self::once())
            ->method('getSession')
            ->willReturn(self::createStub(SessionInterface::class));

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
