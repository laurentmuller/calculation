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

use App\Controller\CspReportController;
use App\Listener\ResponseListener;
use App\Security\SecurityAttributes;
use App\Service\NonceService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ResponseListenerTest extends TestCase
{
    public static function assertHeader(ResponseHeaderBag $headers, string $key, string $value): void
    {
        self::assertTrue($headers->has($key));
        self::assertSame($headers->get($key), $value);
    }

    public static function assertResponse(Response $response, bool $cspSuccess, bool $headersSuccess): void
    {
        $headers = $response->headers;

        // CSP header
        self::assertSame($cspSuccess, $headers->has('Content-Security-Policy'));

        // default headers
        if ($headersSuccess) {
            self::assertHeader($headers, 'referrer-policy', 'same-origin');
            self::assertHeader($headers, 'X-Content-Type-Options', 'nosniff');
            self::assertHeader($headers, 'x-permitted-cross-domain-policies', 'none');
        }
    }

    public function testDebugDevFirewall(): void
    {
        $file = $this->getCspFile();
        $listener = $this->createListener($file, true, SecurityAttributes::DEV_FIREWALL);
        $event = $this->createEvent();
        $listener->onKernelResponse($event);
        self::assertResponse($event->getResponse(), false, false);
    }

    public function testDebugMainFirewall(): void
    {
        $file = $this->getCspFile();
        $listener = $this->createListener($file, true);
        $event = $this->createEvent();
        $listener->onKernelResponse($event);
        self::assertResponse($event->getResponse(), true, true);
    }

    public function testInvalidFile(): void
    {
        $file = __FILE__;
        $listener = $this->createListener($file);
        $event = $this->createEvent();
        $listener->onKernelResponse($event);
        self::assertResponse($event->getResponse(), false, true);
    }

    public function testNotExistFile(): void
    {
        $file = 'fake';
        $listener = $this->createListener($file);
        $event = $this->createEvent();
        $listener->onKernelResponse($event);
        self::assertResponse($event->getResponse(), false, true);
    }

    public function testNotMainRequest(): void
    {
        $file = 'fake';
        $listener = $this->createListener($file);
        $event = $this->createEvent(HttpKernelInterface::SUB_REQUEST);
        $listener->onKernelResponse($event);
        self::assertResponse($event->getResponse(), false, false);
    }

    public function testProdFile(): void
    {
        $file = $this->getCspFile(false);
        $listener = $this->createListener($file);
        $event = $this->createEvent();
        $listener->onKernelResponse($event);
        $response = $event->getResponse();
        self::assertResponse($response, true, true);

        $headers = $response->headers;
        $actual = $headers->get('Content-Security-Policy');
        self::assertIsString($actual);
        self::assertStringContainsString(CspReportController::ROUTE_NAME, $actual);
    }

    public function testSecureHeaders(): void
    {
        $file = $this->getCspFile();
        $listener = $this->createListener($file);
        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('isSecure')
            ->willReturn(true);
        $event = $this->createEvent(request: $request);
        $listener->onKernelResponse($event);

        $key = 'Strict-Transport-Security';
        $value = 'max-age=63072000; includeSubDomains; preload';
        $headers = $event->getResponse()->headers;
        self::assertHeader($headers, $key, $value);
    }

    private function createEvent(
        int $requestType = HttpKernelInterface::MAIN_REQUEST,
        ?Request $request = null
    ): ResponseEvent {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request ??= new Request();
        $response = new Response();

        return new ResponseEvent($kernel, $request, $requestType, $response);
    }

    private function createListener(
        string $file,
        bool $debug = false,
        string $firewall = SecurityAttributes::MAIN_FIREWALL
    ): ResponseListener {
        $cache = new ArrayAdapter();
        $generator = $this->createMockGenerator();
        $service = new NonceService();
        $security = $this->createMockSecurity($firewall);

        return new ResponseListener($file, $debug, $cache, $generator, $service, $security);
    }

    private function createMockGenerator(): MockObject&UrlGeneratorInterface
    {
        $generator = $this->createMock(UrlGeneratorInterface::class);
        $generator->method('generate')
            ->willReturnArgument(0);

        return $generator;
    }

    private function createMockSecurity(string $name = SecurityAttributes::MAIN_FIREWALL): MockObject&Security
    {
        $config = new FirewallConfig($name, '');
        $security = $this->createMock(Security::class);
        $security->method('getFirewallConfig')
            ->willReturn($config);

        return $security;
    }

    private function getCspFile(bool $debug = true): string
    {
        if ($debug) {
            return __DIR__ . '/../../resources/data/csp.dev.json';
        }

        return __DIR__ . '/../../resources/data/csp.prod.json';
    }
}
