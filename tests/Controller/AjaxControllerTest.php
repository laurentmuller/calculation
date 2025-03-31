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

use App\Enums\StrengthLevel;
use App\Tests\EntityTrait\TaskItemTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AjaxControllerTest extends ControllerTestCase
{
    use TaskItemTrait;

    #[\Override]
    public static function getRoutes(): \Generator
    {
        // HTTP_BAD_REQUEST
        yield ['/ajax/task', self::ROLE_USER, Response::HTTP_BAD_REQUEST, Request::METHOD_POST, true];
        yield ['/ajax/task', self::ROLE_ADMIN, Response::HTTP_BAD_REQUEST, Request::METHOD_POST, true];
        yield ['/ajax/task', self::ROLE_SUPER_ADMIN, Response::HTTP_BAD_REQUEST, Request::METHOD_POST, true];

        yield ['/ajax/random/text', self::ROLE_USER];
        yield ['/ajax/random/text', self::ROLE_ADMIN];
        yield ['/ajax/random/text', self::ROLE_SUPER_ADMIN];
    }

    public function testComputeTaskIdEqualZero(): void
    {
        $parameters = [
            'id' => 0,
            'quantity' => 1.0,
            'items' => [1],
        ];
        $this->checkTaskRequest($parameters, Response::HTTP_BAD_REQUEST);
    }

    public function testComputeTaskInvalidItems(): void
    {
        $parameters = [
            'id' => 1,
            'quantity' => 1.0,
            'items' => ['fake value'],
        ];
        $this->checkTaskRequest($parameters, Response::HTTP_BAD_REQUEST);
    }

    public function testComputeTaskItemsEmpty(): void
    {
        $parameters = [
            'id' => 1,
            'quantity' => 1.0,
            'items' => [],
        ];
        $this->checkTaskRequest($parameters, Response::HTTP_BAD_REQUEST);
    }

    public function testComputeTaskNegativeItems(): void
    {
        $parameters = [
            'id' => 1,
            'quantity' => 1.0,
            'items' => [-1],
        ];
        $this->checkTaskRequest($parameters, Response::HTTP_BAD_REQUEST);
    }

    public function testComputeTaskNoFound(): void
    {
        $parameters = [
            'id' => 1_000_000,
            'quantity' => 1.0,
            'items' => [1],
        ];
        $this->checkTaskRequest($parameters, Response::HTTP_OK);
    }

    public function testComputeTaskQuantityEqualZero(): void
    {
        $parameters = [
            'id' => 1,
            'quantity' => 0,
            'items' => [1],
        ];
        $this->checkTaskRequest($parameters, Response::HTTP_BAD_REQUEST);
    }

    public function testComputeTaskSuccess(): void
    {
        $taskItem = $this->getTaskItem();
        $parameters = [
            'id' => $taskItem->getParentEntity()?->getId(),
            'quantity' => 1.0,
            'items' => [$taskItem->getId()],
        ];
        $this->checkTaskRequest($parameters, Response::HTTP_OK);
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

        $content = $this->checkRoute(
            '/ajax/session/set',
            self::ROLE_USER,
            method: Request::METHOD_POST,
            xmlHttpRequest: true,
            parameters: $parameters
        );
        self::assertContentException($content);
    }

    public function testSaveSessionSuccess(): void
    {
        $parameters = [
            'name' => 'key',
            'value' => '{"value": "New"}',
        ];

        $content = $this->checkRoute(
            '/ajax/session/set',
            self::ROLE_USER,
            method: Request::METHOD_POST,
            xmlHttpRequest: true,
            parameters: $parameters
        );
        self::assertContentValid($content);
    }

    public function testSaveTable(): void
    {
        $parameters = [
            'view' => 'table',
        ];

        $content = $this->checkRoute(
            '/ajax/save',
            self::ROLE_USER,
            method: Request::METHOD_POST,
            xmlHttpRequest: true,
            parameters: $parameters
        );
        self::assertContentValid($content);
    }

    protected static function assertContentException(string|false $content): void
    {
        self::assertIsString($content);
        $actual = \json_decode($content, true);
        self::assertIsArray($actual);

        self::assertArrayHasKey('result', $actual);
        self::assertIsBool($actual['result']);
        self::assertFalse($actual['result']);

        self::assertArrayHasKey('message', $actual);
        self::assertIsString($actual['message']);

        self::assertArrayHasKey('exception', $actual);
        self::assertIsArray($actual['exception']);
    }

    protected static function assertContentValid(string|false $content): void
    {
        self::assertIsString($content);
        $actual = \json_decode($content, true);
        self::assertIsBool($actual);
        self::assertTrue($actual);
    }

    #[\Override]
    protected function deleteEntities(): void
    {
        $this->deleteTaskItem();
    }

    #[\Override]
    protected function mustDeleteEntities(): bool
    {
        return true;
    }

    private function checkTaskRequest(array $parameters, int $expected): void
    {
        $this->checkRoute(
            url: '/ajax/task',
            username: self::ROLE_USER,
            expected: $expected,
            method: Request::METHOD_POST,
            xmlHttpRequest: true,
            parameters: $parameters
        );
    }
}
