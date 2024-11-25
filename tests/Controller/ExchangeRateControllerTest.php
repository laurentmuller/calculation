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

namespace App\Tests\Controller;

use App\Model\HttpClientError;
use App\Service\ExchangeRateService;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;

class ExchangeRateControllerTest extends ControllerTestCase
{
    public static function getRoutes(): \Iterator
    {
        yield ['/exchange', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/exchange', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
        yield ['/exchange', self::ROLE_SUPER_ADMIN];

        yield ['/exchange/codes', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/exchange/codes', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
        yield ['/exchange/codes', self::ROLE_SUPER_ADMIN];

        yield ['/exchange/latest/CHF', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/exchange/latest/CHF', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
        yield ['/exchange/latest/CHF', self::ROLE_SUPER_ADMIN];

        yield ['/exchange/rate?baseCode=CHF&targetCode=EUR', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/exchange/rate?baseCode=CHF&targetCode=EUR', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
        yield ['/exchange/rate?baseCode=CHF&targetCode=EUR', self::ROLE_SUPER_ADMIN];
    }

    /**
     * @throws Exception
     */
    public function testGetCodesWithError(): void
    {
        $service = $this->createMockService();
        $this->setService(ExchangeRateService::class, $service);

        $this->checkRoute(
            url: '/exchange/codes',
            username: self::ROLE_SUPER_ADMIN,
            xmlHttpRequest: true
        );
    }

    /**
     * @throws Exception
     */
    public function testGetLatestSuccess(): void
    {
        $data = ['USD' => 1.0];
        $service = $this->createMock(ExchangeRateService::class);
        $service->method('getLatest')
            ->willReturn($data);
        $this->setService(ExchangeRateService::class, $service);

        $this->checkRoute(
            url: '/exchange/latest/CHF',
            username: self::ROLE_SUPER_ADMIN,
            xmlHttpRequest: true
        );
    }

    /**
     * @throws Exception
     */
    public function testGetLatestWithError(): void
    {
        $service = $this->createMockService();
        $this->setService(ExchangeRateService::class, $service);

        $this->checkRoute(
            url: '/exchange/latest/CHF',
            username: self::ROLE_SUPER_ADMIN,
            xmlHttpRequest: true
        );
    }

    /**
     * @throws Exception
     */
    public function testGetRateNull(): void
    {
        $data = null;
        $service = $this->createMock(ExchangeRateService::class);
        $service->method('getRateAndDates')
            ->willReturn($data);
        $this->setService(ExchangeRateService::class, $service);

        $this->checkRoute(
            url: '/exchange/rate?baseCode=CHF&targetCode=EUR',
            username: self::ROLE_SUPER_ADMIN,
            xmlHttpRequest: true
        );
    }

    /**
     * @throws Exception
     */
    public function testGetRateSuccess(): void
    {
        $data = [
            'rate' => 1.1,
            'next' => null,
            'update' => null,
        ];
        $service = $this->createMock(ExchangeRateService::class);
        $service->method('getRateAndDates')
            ->willReturn($data);
        $this->setService(ExchangeRateService::class, $service);

        $this->checkRoute(
            url: '/exchange/rate?baseCode=CHF&targetCode=EUR',
            username: self::ROLE_SUPER_ADMIN,
            xmlHttpRequest: true
        );
    }

    /**
     * @throws Exception
     */
    public function testGetRateWithError(): void
    {
        $service = $this->createMockService();
        $this->setService(ExchangeRateService::class, $service);

        $this->checkRoute(
            url: '/exchange/rate?baseCode=CHF&targetCode=EUR',
            username: self::ROLE_SUPER_ADMIN,
            xmlHttpRequest: true
        );
    }

    /**
     * @throws Exception
     */
    private function createMockService(): MockObject&ExchangeRateService
    {
        $error = new HttpClientError(100, 'Fake Message');
        $service = $this->createMock(ExchangeRateService::class);
        $service->method('hasLastError')
            ->willReturn(true);
        $service->method('getLastError')
            ->willReturn($error);

        return $service;
    }
}
