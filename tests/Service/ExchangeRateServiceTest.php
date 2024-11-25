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
use App\Service\ExchangeRateService;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

class ExchangeRateServiceTest extends TestCase
{
    use TranslatorMockTrait;

    private const ERROR_CODE = 404;
    private const ERROR_MESSAGE = 'Error Message';

    /**
     * @throws Exception
     */
    public function testGetLatestError(): void
    {
        $response = $this->getErrorResponse();
        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->getLatest('chf');
        self::assertEmpty($actual);
        self::assertError($service);
    }

    /**
     * @throws Exception
     */
    public function testGetLatestSuccess(): void
    {
        $response = new JsonMockResponse(
            [
                'result' => 'success',
                'conversion_rates' => [
                    'chf' => 1.01,
                ],
            ]
        );
        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->getLatest('chf');
        self::assertNotEmpty($actual);
        self::assertCount(1, $actual);
        self::assertArrayHasKey('chf', $actual);
        self::assertSame(1.01, $actual['chf']);
    }

    /**
     * @throws Exception
     */
    public function testGetLatestWithNull(): void
    {
        $response = new JsonMockResponse(
            [
                'result' => 'success',
            ]
        );
        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->getLatest('chf');
        self::assertEmpty($actual);
    }

    /**
     * @throws Exception
     */
    public function testGetQuotaError(): void
    {
        $response = $this->getErrorResponse();
        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->getQuota();
        self::assertNull($actual);
        self::assertError($service);
    }

    /**
     * @throws Exception
     */
    public function testGetQuotaSuccess(): void
    {
        $response = new JsonMockResponse(
            [
                'result' => 'success',
                'refresh_day_of_month' => 10,
                'plan_quota' => 1000,
                'requests_remaining' => 500,
                'documentation' => 'Documentation',
            ]
        );
        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->getQuota();
        self::assertIsArray($actual);
        self::assertArrayHasKey('allowed', $actual);
        self::assertArrayHasKey('remaining', $actual);
        self::assertArrayHasKey('documentation', $actual);
        self::assertArrayHasKey('date', $actual);
        self::assertSame(1000, $actual['allowed']);
        self::assertSame(500, $actual['remaining']);
        self::assertSame('Documentation', $actual['documentation']);
    }

    /**
     * @throws Exception
     */
    public function testGetRateAndDatesError(): void
    {
        $response = $this->getErrorResponse();
        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->getRateAndDates('CHF', 'USD');
        self::assertNull($actual);
        self::assertError($service);
    }

    /**
     * @throws Exception
     */
    public function testGetRateAndDatesSuccess(): void
    {
        $response = new JsonMockResponse(
            [
                'result' => 'success',
                'conversion_rate' => 1.00,
                'time_last_update_unix' => 1,
                'time_next_update_unix' => 100 + \time(),
            ]
        );
        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->getRateAndDates('CHF', 'USD');
        self::assertIsArray($actual);
        self::assertArrayHasKey('rate', $actual);
        self::assertArrayHasKey('next', $actual);
        self::assertArrayHasKey('update', $actual);
        self::assertSame(1.00, $actual['rate']);
        self::assertSame(1, $actual['update']);
        self::assertGreaterThan(0, $actual['next']);
    }

    /**
     * @throws Exception
     */
    public function testGetRateAndDatesWithNull(): void
    {
        $response = new JsonMockResponse(
            [
                'result' => 'success',
                'conversion_rate' => null,
            ]
        );
        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->getRateAndDates('CHF', 'USD');
        self::assertNull($actual);
    }

    /**
     * @throws Exception
     */
    public function testGetRateError(): void
    {
        $response = $this->getErrorResponse();
        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->getRate('CHF', 'USD');
        self::assertSame(0.0, $actual);
        self::assertError($service);
    }

    /**
     * @throws Exception
     */
    public function testGetRateSuccess(): void
    {
        $response = new JsonMockResponse(
            [
                'result' => 'success',
                'conversion_rate' => 1.00,
            ]
        );
        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->getRate('CHF', 'USD');
        self::assertSame(1.00, $actual);
    }

    /**
     * @throws Exception
     */
    public function testGetRateWithNull(): void
    {
        $response = new JsonMockResponse(
            [
                'result' => 'success',
            ]
        );
        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->getRate('CHF', 'USD');
        self::assertSame(0.0, $actual);
    }

    /**
     * @throws Exception
     */
    public function testGetSupportedCodesError(): void
    {
        $response = $this->getErrorResponse();
        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->getSupportedCodes();
        self::assertEmpty($actual);
        self::assertError($service);
    }

    /**
     * @throws Exception
     */
    public function testGetSupportedCodesSuccess(): void
    {
        $response = new JsonMockResponse(
            [
                'result' => 'success',
                'supported_codes' => [
                    [
                        'CHF',
                        'Swiss Franc',
                    ],
                ],
            ]
        );
        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->getSupportedCodes();
        self::assertNotEmpty($actual);
        self::assertCount(1, $actual);
        self::assertArrayHasKey('CHF', $actual);
        $actual = $actual['CHF'];
        self::assertArrayHasKey('symbol', $actual);
        self::assertArrayHasKey('name', $actual);
        self::assertArrayHasKey('numericCode', $actual);
        self::assertArrayHasKey('fractionDigits', $actual);
        self::assertArrayHasKey('roundingIncrement', $actual);
        self::assertSame('CHF', $actual['symbol']);
        self::assertSame(756, $actual['numericCode']);
        self::assertSame(2, $actual['fractionDigits']);
        self::assertSame(0, $actual['roundingIncrement']);
    }

    /**
     * @throws Exception
     */
    public function testGetSupportedCodesWithNull(): void
    {
        $response = new JsonMockResponse(
            [
                'result' => 'success',
            ]
        );
        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $actual = $service->getSupportedCodes();
        self::assertEmpty($actual);
    }

    /**
     * @throws Exception
     */
    public function testGetWithException(): void
    {
        $response = new MockResponse([
            new \RuntimeException('Error at transport level'),
        ]);
        $client = new MockHttpClient([$response]);
        $service = $this->createService();
        $service->setClient($client);
        $service->getQuota();
        self::assertError($service, 'unknown');
    }

    protected static function assertError(ExchangeRateService $service, string $message = self::ERROR_MESSAGE): void
    {
        $actual = $service->getLastError();
        self::assertInstanceOf(HttpClientError::class, $actual);
        self::assertSame(self::ERROR_CODE, $actual->getCode());
        self::assertSame($message, $actual->getMessage());
    }

    /**
     * @throws Exception
     */
    private function createService(): ExchangeRateService
    {
        $key = 'fake';
        $cache = new ArrayAdapter();
        $logger = $this->createMock(LoggerInterface::class);
        $translator = $this->createMockTranslator();

        return new ExchangeRateService($key, $cache, $logger, $translator);
    }

    private function getErrorResponse(): JsonMockResponse
    {
        return new JsonMockResponse(
            [
                'result' => 'false',
                'error-type' => self::ERROR_MESSAGE,
            ]
        );
    }
}
