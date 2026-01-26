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

use App\Model\CalculationAdjustResult;
use App\Service\CalculationService;
use App\Tests\EntityTrait\CalculationTrait;
use App\Utils\StringUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AjaxCalculationControllerTest extends ControllerTestCase
{
    use CalculationTrait;

    private const string UPDATE_ROUTE_NAME = '/calculation/update';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->getCalculation();
    }

    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield [self::UPDATE_ROUTE_NAME, self::ROLE_USER,  Response::HTTP_OK, Request::METHOD_POST, true];
    }

    public function testUpdate(): void
    {
        $parameter = \json_encode([
            'adjust' => true,
            'userMargin' => -0.16,
            'groups' => [
                ['id' => 10, 'total' => 83.5],
            ],
        ]);

        $this->loginUsername('ROLE_USER');
        $this->client->request(Request::METHOD_POST, self::UPDATE_ROUTE_NAME, [$parameter]);
        $response = $this->client->getResponse();
        self::assertTrue($response->isOk());
        self::assertInstanceOf(JsonResponse::class, $response);
        $content = $response->getContent();
        self::assertIsString($content);
        $data = StringUtils::decodeJson($content);
        self::assertArrayHasKey('result', $data);
        self::assertTrue($data['result']);
    }

    public function testUpdateWithAdjust(): void
    {
        $data = new CalculationAdjustResult(
            overallBelow: true,
            overallMargin: 0.0,
            overallTotal: 0.0,
            userMargin: 0.0,
            minMargin: 1.1,
            groups: new ArrayCollection([]),
            adjust: true
        );
        $service = $this->createMock(CalculationService::class);
        $service->method('createParameters')
            ->willReturn($data);
        $this->setService(CalculationService::class, $service);

        $parameters = [
            'adjust' => true,
            'userMargin' => 0.0,
            'groups' => [],
        ];
        $this->checkRoute(
            url: self::UPDATE_ROUTE_NAME,
            username: self::ROLE_USER,
            method: Request::METHOD_POST,
            xmlHttpRequest: true,
            parameters: $parameters
        );
    }

    public function testUpdateWithException(): void
    {
        $service = $this->createMock(CalculationService::class);
        $service->method('createParameters')
            ->willThrowException(new \Exception('Fake Message'));
        $this->setService(CalculationService::class, $service);
        $this->checkRoute(
            url: self::UPDATE_ROUTE_NAME,
            username: self::ROLE_USER,
            method: Request::METHOD_POST,
            xmlHttpRequest: true
        );
    }

    public function testUpdateWithResultFalse(): void
    {
        $data = new CalculationAdjustResult(
            overallBelow: true,
            overallMargin: 0.0,
            overallTotal: 0.0,
            userMargin: 0.0,
            minMargin: 0.0,
            groups: new ArrayCollection([]),
            adjust: true,
            result: false,
        );

        $service = $this->createMock(CalculationService::class);
        $service->method('createParameters')
            ->willReturn($data);
        $this->setService(CalculationService::class, $service);

        $this->checkRoute(
            url: self::UPDATE_ROUTE_NAME,
            username: self::ROLE_USER,
            method: Request::METHOD_POST,
            xmlHttpRequest: true
        );
    }
}
