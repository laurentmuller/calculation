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
use App\Service\IpStackService;
use App\Service\PositionService;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;

final class IpStackServiceTest extends TestCase
{
    use TranslatorMockTrait;

    private const int ERROR_CODE = 404;
    private const string ERROR_MESSAGE = 'Error Message';

    public function testGetIpInfoError(): void
    {
        $response = $this->getErrorResponse();
        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->getIpInfo();
        self::assertEmpty($actual);
        $this->assertError($service);
    }

    public function testGetIpInfoSuccess(): void
    {
        $response = $this->getValidResponse();
        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->getIpInfo();
        self::assertIsArray($actual);
    }

    public function testGetIpInfoWithClientIp(): void
    {
        $response = $this->getValidResponse();
        $request = $this->createMock(Request::class);
        $request->method('getClientIp')
            ->willReturn('62.202.191.50');

        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->getIpInfo($request);
        self::assertIsArray($actual);
    }

    public function testGetIpInfoWithRequest(): void
    {
        $response = $this->getValidResponse();
        $request = new Request();
        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->getIpInfo($request);
        self::assertIsArray($actual);
    }

    public function testGetWithException(): void
    {
        $response = new MockResponse([
            new \RuntimeException('Error at transport level'),
        ]);
        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $service->getIpInfo();
        $this->assertError($service, 'unknown');
    }

    private function assertError(IpStackService $service, string $message = self::ERROR_MESSAGE): void
    {
        $actual = $service->getLastError();
        self::assertInstanceOf(HttpClientError::class, $actual);
        self::assertSame(self::ERROR_CODE, $actual->getCode());
        self::assertSame($message, $actual->getMessage());
    }

    private function createService(): IpStackService
    {
        $translator = $this->createMockTranslator();
        $service = new PositionService($translator);

        return new IpStackService(
            'fake',
            new ArrayAdapter(),
            self::createStub(LoggerInterface::class),
            $service,
            $translator
        );
    }

    private function getErrorResponse(): JsonMockResponse
    {
        return new JsonMockResponse(
            [
                'error' => [
                    'code' => self::ERROR_CODE,
                    'type' => self::ERROR_MESSAGE,
                ],
            ]
        );
    }

    private function getValidResponse(): JsonMockResponse
    {
        return new JsonMockResponse(
            [
                'ip' => '62.202.191.50',
                'hostname' => '50.191.202.62',
                'type' => 'ipv4',
                'continent_code' => 'EU',
                'continent_name' => 'Europe',
                'country_code' => 'CH',
                'country_name' => 'Suisse',
                'region_code' => 'ZH',
                'region_name' => 'canton de Zurich',
                'city' => 'Zurich',
                'zip' => '8045',
                'latitude' => 47.36246871948242,
                'longitude' => 8.521849632263184,
            ]
        );
    }
}
