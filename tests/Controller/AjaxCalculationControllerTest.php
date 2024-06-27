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

use App\Controller\AbstractController;
use App\Controller\AjaxCalculationController;
use App\Tests\EntityTrait\CalculationTrait;
use App\Utils\StringUtils;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(AbstractController::class)]
#[CoversClass(AjaxCalculationController::class)]
class AjaxCalculationControllerTest extends ControllerTestCase
{
    use CalculationTrait;

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
        yield ['/ajax/dialog/item', self::ROLE_USER, Response::HTTP_OK, Request::METHOD_GET, true];
        yield ['/ajax/dialog/item', self::ROLE_ADMIN, Response::HTTP_OK, Request::METHOD_GET, true];
        yield ['/ajax/dialog/item', self::ROLE_SUPER_ADMIN, Response::HTTP_OK, Request::METHOD_GET, true];

        yield ['/ajax/dialog/task', self::ROLE_USER, Response::HTTP_OK, Request::METHOD_GET, true];
        yield ['/ajax/dialog/task', self::ROLE_ADMIN, Response::HTTP_OK, Request::METHOD_GET, true];
        yield ['/ajax/dialog/task', self::ROLE_SUPER_ADMIN, Response::HTTP_OK, Request::METHOD_GET, true];
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
        $this->client->request(Request::METHOD_POST, '/ajax/update', $parameters);
        $response = $this->client->getResponse();
        self::assertTrue($response->isOk());
        self::assertInstanceOf(JsonResponse::class, $response);
        $content = $response->getContent();
        self::assertIsString($content);
        $data = StringUtils::decodeJson($content);
        self::assertArrayHasKey('result', $data);
        self::assertTrue($data['result']);
    }
}
