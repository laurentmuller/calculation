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

use App\Service\CalculationService;
use App\Tests\EntityTrait\CalculationTrait;
use App\Utils\StringUtils;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AjaxCalculationControllerTest extends ControllerTestCase
{
    use CalculationTrait;

    private const UPDATE_ROUTE_NAME = '/calculation/update';

    /**
     * @throws ORMException
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->getCalculation();
    }

    public static function getRoutes(): \Iterator
    {
        yield [self::UPDATE_ROUTE_NAME, self::ROLE_USER,  Response::HTTP_OK, Request::METHOD_POST, true];
    }

    public function testUpdate(): void
    {
        $items = [
            [
                'position' => '0',
                'description' => 'Avery 800 - Coulé Teinté',
                'unit' => 'm2',
                'price' => '16.7',
                'quantity' => '5',
            ],
        ];
        $categories = [
            [
                'position' => '0',
                'code' => 'Film Teinté',
                'category' => '5',
                'items' => $items,
            ],
        ];
        $groups = [
            [
                'position' => '0',
                'code' => 'Matériel',
                'group' => '10',
                'categories' => $categories,
            ],
        ];
        $calculation = [
            'customer' => 'Customer',
            'description' => 'Description',
            'date' => '2024-05-17',
            'state' => '2',
            'userMargin' => '-16',
            'groups' => $groups,
        ];

        $parameters = [
            'adjust' => true,
            'calculation' => $calculation,
        ];

        $this->loginUsername('ROLE_USER');
        $this->client->request(Request::METHOD_POST, self::UPDATE_ROUTE_NAME, $parameters);
        $response = $this->client->getResponse();
        self::assertTrue($response->isOk());
        self::assertInstanceOf(JsonResponse::class, $response);
        $content = $response->getContent();
        self::assertIsString($content);
        $data = StringUtils::decodeJson($content);
        self::assertArrayHasKey('result', $data);
        self::assertTrue($data['result']);
    }

    /**
     * @throws Exception
     */
    public function testUpdateWithAdjust(): void
    {
        $data = [
            'result' => true,
            'overall_below' => true,
            'overall_margin' => 0.0,
            'overall_total' => 0.0,
            'min_margin' => 1.1,
            'user_margin' => 0.0,
            'groups' => [],
        ];
        $service = $this->createMock(CalculationService::class);
        $service->method('createGroupsFromData')
            ->willReturn($data);
        $service->method('adjustUserMargin')
            ->willReturn($data);
        $this->setService(CalculationService::class, $service);

        $parameters = [
            'adjust' => true,
        ];
        $this->checkRoute(
            url: self::UPDATE_ROUTE_NAME,
            username: self::ROLE_USER,
            method: Request::METHOD_POST,
            xmlHttpRequest: true,
            parameters: $parameters
        );
    }

    /**
     * @throws Exception
     */
    public function testUpdateWithException(): void
    {
        $service = $this->createMock(CalculationService::class);
        $service->method('createGroupsFromData')
            ->willThrowException(new \Exception('Fake Message'));
        $this->setService(CalculationService::class, $service);
        $this->checkRoute(
            url: self::UPDATE_ROUTE_NAME,
            username: self::ROLE_USER,
            method: Request::METHOD_POST,
            xmlHttpRequest: true
        );
    }

    /**
     * @throws Exception
     */
    public function testUpdateWithResultFalse(): void
    {
        $data = [
            'result' => false,
        ];
        $service = $this->createMock(CalculationService::class);
        $service->method('createGroupsFromData')
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
