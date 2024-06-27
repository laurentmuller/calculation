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
use App\Controller\AjaxController;
use App\Enums\StrengthLevel;
use App\Tests\EntityTrait\TaskItemTrait;
use App\Utils\StringUtils;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(AbstractController::class)]
#[CoversClass(AjaxController::class)]
class AjaxControllerTest extends ControllerTestCase
{
    use TaskItemTrait;

    public static function getRoutes(): \Iterator
    {
        yield ['/ajax/task', self::ROLE_USER, Response::HTTP_OK, Request::METHOD_POST, true];
        yield ['/ajax/task', self::ROLE_ADMIN, Response::HTTP_OK, Request::METHOD_POST, true];
        yield ['/ajax/task', self::ROLE_SUPER_ADMIN, Response::HTTP_OK, Request::METHOD_POST, true];

        yield ['/ajax/random/text', self::ROLE_USER];
        yield ['/ajax/random/text', self::ROLE_ADMIN];
        yield ['/ajax/random/text', self::ROLE_SUPER_ADMIN];

        yield ['/ajax/dialog/page', self::ROLE_USER];
        yield ['/ajax/dialog/page', self::ROLE_ADMIN];
        yield ['/ajax/dialog/page', self::ROLE_SUPER_ADMIN];

        $query = '/ajax/license?file=vendor/symfony/runtime/LICENSE';
        yield [$query, self::ROLE_SUPER_ADMIN, Response::HTTP_OK, Request::METHOD_GET, true];

        $query = '/ajax/license?file=fake';
        yield [$query, self::ROLE_ADMIN, Response::HTTP_OK, Request::METHOD_GET, true];

        $query = '/ajax/license?file=tests/Data/empty.txt';
        yield [$query, self::ROLE_ADMIN, Response::HTTP_OK, Request::METHOD_GET, true];
    }

    public function testComputeTaskEmpty(): void
    {
        $parameters = [
            'id' => 0,
        ];

        $this->checkRoute(
            '/ajax/task',
            self::ROLE_USER,
            method: Request::METHOD_POST,
            xmlHttpRequest: true,
            parameters: $parameters
        );
    }

    /**
     * @throws ORMException
     */
    public function testComputeTaskSuccess(): void
    {
        $taskItem = $this->getTaskItem();
        $parameters = [
            'id' => $taskItem->getParentEntity()?->getId(),
            'items' => [$taskItem->getId()],
        ];
        $this->checkRoute(
            '/ajax/task',
            self::ROLE_USER,
            method: Request::METHOD_POST,
            xmlHttpRequest: true,
            parameters: $parameters
        );
    }

    public function testDialogSort(): void
    {
        $parameters = [
            [
                'field' => 'description',
                'title' => 'Description',
                'order' => 'asc',
                'default' => true,
            ],
        ];

        $this->checkRoute(
            '/ajax/dialog/sort',
            self::ROLE_USER,
            method: Request::METHOD_POST,
            xmlHttpRequest: true,
            content: StringUtils::encodeJson($parameters)
        );
    }

    public function testPassword(): void
    {
        $parameters = [
            'password' => 0,
            'strength' => StrengthLevel::NONE->value,
            'email' => null,
            'user' => null,
        ];

        $this->checkRoute(
            '/ajax/password',
            self::ROLE_USER,
            method: Request::METHOD_POST,
            xmlHttpRequest: true,
            parameters: $parameters
        );
    }

    public function testSaveSessionInvalid(): void
    {
        $parameters = [
            'name' => 'key',
            'value' => '{"value"',
        ];

        $this->checkRoute(
            '/ajax/session/set',
            self::ROLE_USER,
            method: Request::METHOD_POST,
            xmlHttpRequest: true,
            parameters: $parameters
        );
    }

    public function testSaveSessionSuccess(): void
    {
        $parameters = [
            'name' => 'key',
            'value' => '{"value": "New"}',
        ];

        $this->checkRoute(
            '/ajax/session/set',
            self::ROLE_USER,
            method: Request::METHOD_POST,
            xmlHttpRequest: true,
            parameters: $parameters
        );
    }

    public function testSaveTable(): void
    {
        $parameters = [
            'view' => 'table',
        ];

        $this->checkRoute(
            '/ajax/save',
            self::ROLE_USER,
            method: Request::METHOD_POST,
            xmlHttpRequest: true,
            parameters: $parameters
        );
    }

    /**
     * @throws ORMException
     */
    protected function deleteEntities(): void
    {
        $this->deleteTaskItem();
    }

    protected function mustDeleteEntities(): bool
    {
        return true;
    }
}
