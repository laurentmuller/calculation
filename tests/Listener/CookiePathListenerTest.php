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

use App\Listener\CookiePathListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class CookiePathListenerTest extends TestCase
{
    public function testDefaultBasePath(): void
    {
        $event = $this->createEvent();
        $listener = $this->createListener();
        $listener->onKernelResponse($event);
        self::assertTrue($event->getResponse()->isOk());
    }

    public function testNoCookies(): void
    {
        $request = self::createMock(Request::class);
        $request->expects(self::once())
            ->method('getBasePath')
            ->willReturn('/fake');

        $event = $this->createEvent(request: $request);
        $listener = $this->createListener();
        $listener->onKernelResponse($event);
    }

    public function testNotMainRequest(): void
    {
        $event = $this->createEvent(requestType: HttpKernelInterface::SUB_REQUEST);
        $listener = $this->createListener();
        $listener->onKernelResponse($event);
        self::assertFalse($event->isMainRequest());
    }

    public function testWithCookieSkip(): void
    {
        $request = self::createMock(Request::class);
        $request->expects(self::once())
            ->method('getBasePath')
            ->willReturn('/fake');

        $cookie = Cookie::create('name')
            ->withPath('/fake');
        $headers = new ResponseHeaderBag();
        $headers->setCookie($cookie);
        $response = new Response(headers: $headers);

        $event = $this->createEvent(request: $request, response: $response);
        $listener = $this->createListener('/production');
        $listener->onKernelResponse($event);
        self::assertTrue($event->getResponse()->isOk());
    }

    public function testWithCookieUpdate(): void
    {
        $request = self::createMock(Request::class);
        $request->expects(self::once())
            ->method('getBasePath')
            ->willReturn('/fake');

        $cookie = Cookie::create('name')
            ->withValue('value');
        $headers = new ResponseHeaderBag();
        $headers->setCookie($cookie);
        $response = new Response(headers: $headers);

        $event = $this->createEvent(request: $request, response: $response);
        $listener = $this->createListener('/production');
        $listener->onKernelResponse($event);
        $cookies = $headers->getCookies();
        self::assertCount(1, $cookies);
        $actual = $cookies[0];
        self::assertSame('/production', $actual->getPath());
    }

    private function createEvent(
        ?Request $request = null,
        ?Response $response = null,
        int $requestType = HttpKernelInterface::MAIN_REQUEST
    ): ResponseEvent {
        return new ResponseEvent(
            kernel: self::createStub(HttpKernelInterface::class),
            request: $request ?? new Request(),
            requestType: $requestType,
            response: $response ?? new Response(),
        );
    }

    private function createListener(string $cookiePath = '/'): CookiePathListener
    {
        return new CookiePathListener($cookiePath);
    }
}
