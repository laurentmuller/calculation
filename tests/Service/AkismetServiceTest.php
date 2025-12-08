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

namespace App\Tests\Service;

use App\Model\HttpClientError;
use App\Service\AkismetService;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class AkismetServiceTest extends TestCase
{
    use TranslatorMockTrait;

    private const ERROR_CODE = 1000;
    private const ERROR_MESSAGE = 'Error Message';

    private MockObject&RequestStack $requestStack;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->requestStack = $this->createMock(RequestStack::class);
    }

    public function testActivityException(): void
    {
        $client = new MockHttpClient();
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->activity();
        self::assertFalse($actual);
        self::assertInstanceOf(HttpClientError::class, $service->getLastError());
    }

    public function testActivityInvalidCode(): void
    {
        $client = new MockHttpClient([$this->getInvalidCodeResponse()]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->activity();
        self::assertFalse($actual);
    }

    public function testActivityLastError(): void
    {
        $client = new MockHttpClient([$this->getLastErrorResponse()]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->activity();
        self::assertFalse($actual);
        $actual = $service->getLastError();
        self::assertInstanceOf(HttpClientError::class, $actual);
        self::assertSame(self::ERROR_CODE, $actual->getCode());
        self::assertSame(self::ERROR_MESSAGE, $actual->getMessage());
    }

    public function testActivitySuccess(): void
    {
        $response = new JsonMockResponse(
            [
                '2024-06' => [
                    [
                        'site' => '127.0.0.1:8000',
                        'api_calls' => '2',
                        'spam' => '0',
                        'ham' => '2',
                        'missed_spam' => '0',
                        'false_positives' => '0',
                        'is_revoked' => false,
                    ],
                ],
                'limit' => 500,
                'offset' => 0,
                'total' => 1,
            ]
        );

        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->activity();
        self::assertIsArray($actual);
    }

    public function testEmptyKey(): void
    {
        $key = '';
        $cache = new ArrayAdapter();
        $logger = $this->createMock(LoggerInterface::class);
        $security = $this->createMock(Security::class);
        $translator = $this->createMockTranslator();

        self::expectException(\InvalidArgumentException::class);
        new AkismetService(
            $key,
            $cache,
            $logger,
            $security,
            $this->requestStack,
            $translator
        );
    }

    public function testIsSpamInvalidCode(): void
    {
        $request = new Request();
        $this->requestStack->method('getCurrentRequest')
            ->willReturn($request);

        $client = new MockHttpClient([$this->getInvalidCodeResponse()]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->isSpam('content');
        self::assertFalse($actual);
    }

    public function testIsSpamNoRequest(): void
    {
        $this->requestStack->method('getCurrentRequest')
            ->willReturn(null);

        $service = $this->createService();
        $actual = $service->isSpam('content');
        self::assertTrue($actual);
    }

    public function testIsSpamSuccess(): void
    {
        $request = new Request();
        $this->requestStack->method('getCurrentRequest')
            ->willReturn($request);

        $service = $this->createService();
        $actual = $service->isSpam('content');
        self::assertTrue($actual);
    }

    public function testIsValidKeyInvalidCode(): void
    {
        $request = new Request();
        $this->requestStack->method('getCurrentRequest')
            ->willReturn($request);

        $client = new MockHttpClient([$this->getInvalidCodeResponse()]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->isValidKey();
        self::assertFalse($actual);
    }

    public function testIsValidKeyLastError(): void
    {
        $request = new Request();
        $this->requestStack->method('getCurrentRequest')
            ->willReturn($request);

        $client = new MockHttpClient([$this->getLastErrorResponse()]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->isValidKey();
        self::assertFalse($actual);
        $actual = $service->getLastError();
        self::assertInstanceOf(HttpClientError::class, $actual);
        self::assertSame(self::ERROR_CODE, $actual->getCode());
        self::assertSame(self::ERROR_MESSAGE, $actual->getMessage());
    }

    public function testIsValidKeyNoRequest(): void
    {
        $this->requestStack->method('getCurrentRequest')
            ->willReturn(null);

        $service = $this->createService();
        $actual = $service->isValidKey();
        self::assertFalse($actual);
    }

    public function testIsValidKeySuccess(): void
    {
        $request = new Request();
        $this->requestStack->method('getCurrentRequest')
            ->willReturn($request);

        $response = new MockResponse('valid');
        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->isValidKey();
        self::assertTrue($actual);
    }

    public function testUsageException(): void
    {
        $client = new MockHttpClient();
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->usage();
        self::assertFalse($actual);
        self::assertInstanceOf(HttpClientError::class, $service->getLastError());
    }

    public function testUsageInvalidCode(): void
    {
        $client = new MockHttpClient([$this->getInvalidCodeResponse()]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->usage();
        self::assertFalse($actual);
    }

    public function testUsageLastError(): void
    {
        $client = new MockHttpClient([$this->getLastErrorResponse()]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->usage();
        self::assertFalse($actual);
        $actual = $service->getLastError();
        self::assertInstanceOf(HttpClientError::class, $actual);
        self::assertSame(self::ERROR_CODE, $actual->getCode());
        self::assertSame(self::ERROR_MESSAGE, $actual->getMessage());
    }

    public function testUsageSuccess(): void
    {
        $response = new JsonMockResponse(
            [
                'limit' => 'none',
                'usage' => 8,
                'percentage' => 0.2,
                'throttled' => false,
            ]
        );

        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->usage();
        self::assertIsArray($actual);
    }

    public function testVerifyLastError(): void
    {
        $request = new Request();
        $this->requestStack->method('getCurrentRequest')
            ->willReturn($request);

        $client = new MockHttpClient([$this->getLastErrorResponse()]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->isSpam('content');
        self::assertFalse($actual);
        $actual = $service->getLastError();
        self::assertInstanceOf(HttpClientError::class, $actual);
        self::assertSame(self::ERROR_CODE, $actual->getCode());
        self::assertSame(self::ERROR_MESSAGE, $actual->getMessage());
    }

    private function createService(): AkismetService
    {
        $key = 'fake';
        $cache = new ArrayAdapter();
        $logger = $this->createMock(LoggerInterface::class);
        $security = $this->createMock(Security::class);
        $translator = $this->createMockTranslator();

        return new AkismetService(
            $key,
            $cache,
            $logger,
            $security,
            $this->requestStack,
            $translator
        );
    }

    private function getInvalidCodeResponse(): JsonMockResponse
    {
        return new JsonMockResponse(
            [
                'error' => [
                    'code' => self::ERROR_CODE,
                    'message' => self::ERROR_MESSAGE,
                ],
            ],
            ['http_code' => self::ERROR_CODE]
        );
    }

    private function getLastErrorResponse(): JsonMockResponse
    {
        return new JsonMockResponse(
            [
                'error' => [
                    'code' => self::ERROR_CODE,
                    'message' => self::ERROR_MESSAGE,
                ],
            ],
            [
                'response_headers' => [
                    'x-akismet-alert-code' => self::ERROR_CODE,
                    'x-akismet-alert-msg' => self::ERROR_MESSAGE,
                ],
            ]
        );
    }
}
