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
use App\Service\NonceService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[CoversClass(ResponseListener::class)]
class ResponseListenerTest extends TestCase
{
    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testDebugDevFirewall(): void
    {
        $file = $this->getCspFile();
        $listener = $this->createListener($file, true, ResponseListener::FIREWALL_DEV);
        $event = $this->createEvent();
        $listener->onKernelResponse($event);
        self::assertResponse($event->getResponse(), false);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testDebugMainFirewall(): void
    {
        $file = $this->getCspFile();
        $listener = $this->createListener($file, true);
        $event = $this->createEvent();
        $listener->onKernelResponse($event);
        self::assertResponse($event->getResponse());
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testInvalidFile(): void
    {
        $file = __FILE__;
        $listener = $this->createListener($file);
        $event = $this->createEvent();
        $listener->onKernelResponse($event);
        self::assertResponse($event->getResponse(), false);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testNotExistFile(): void
    {
        $file = 'fake';
        $listener = $this->createListener($file);
        $event = $this->createEvent();
        $listener->onKernelResponse($event);
        self::assertResponse($event->getResponse(), false);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testNotMainRequest(): void
    {
        $file = 'fake';
        $listener = $this->createListener($file);
        $event = $this->createEvent(HttpKernelInterface::SUB_REQUEST);
        $listener->onKernelResponse($event);
        self::assertResponse($event->getResponse(), false);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testProdFile(): void
    {
        $file = $this->getCspFile(false);
        $listener = $this->createListener($file);
        $event = $this->createEvent();
        $listener->onKernelResponse($event);
        $response = $event->getResponse();
        self::assertResponse($response);

        $headers = $response->headers;
        $actual = $headers->get('Content-Security-Policy');
        self::assertIsString($actual);
        self::assertStringContainsString(CspReportController::ROUTE_NAME, $actual);
    }

    protected static function assertResponse(Response $response, bool $success = true): void
    {
        $headers = $response->headers;

        // CSP headers
        self::assertSame($success, $headers->has('X-WebKit-CSP'));
        self::assertSame($success, $headers->has('Content-Security-Policy'));
        self::assertSame($success, $headers->has('X-Content-Security-Policy'));

        if (!$success) {
            return;
        }

        // default headers
        self::assertTrue($headers->has('referrer-policy'));
        self::assertTrue($headers->has('X-FRAME-OPTIONS'));
        self::assertTrue($headers->has('X-Content-Type-Options'));
        self::assertTrue($headers->has('x-permitted-cross-domain-policies'));

        self::assertSame($headers->get('referrer-policy'), 'same-origin');
        self::assertSame($headers->get('X-FRAME-OPTIONS'), 'sameorigin');
        self::assertSame($headers->get('X-Content-Type-Options'), 'nosniff');
        self::assertSame($headers->get('x-permitted-cross-domain-policies'), 'none');
    }

    /**
     * @throws Exception
     */
    private function createEvent(int $requestType = HttpKernelInterface::MAIN_REQUEST): ResponseEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $response = new Response();

        return new ResponseEvent($kernel, $request, $requestType, $response);
    }

    /**
     * @throws Exception
     */
    private function createListener(
        string $file,
        bool $debug = false,
        string $firewall = ResponseListener::FIREWALL_MAIN
    ): ResponseListener {
        $cache = new ArrayAdapter();
        $generator = $this->createMockGenerator();
        $service = new NonceService();
        $security = $this->createMockSecurity($firewall);

        return new ResponseListener($file, $debug, $cache, $generator, $service, $security);
    }

    /**
     * @throws Exception
     */
    private function createMockGenerator(): MockObject&UrlGeneratorInterface
    {
        $generator = $this->createMock(UrlGeneratorInterface::class);
        $generator->method('generate')
            ->willReturnArgument(0);

        return $generator;
    }

    /**
     * @throws Exception
     */
    private function createMockSecurity(string $name = ResponseListener::FIREWALL_MAIN): MockObject&Security
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
