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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abstract unit test case for entity controllers.
 */
abstract class EntityControllerTestCase extends ControllerTestCase
{
    protected function checkAddEntity(
        string $uri,
        array $data = [],
        string $userName = self::ROLE_ADMIN,
        string $method = Request::METHOD_POST,
    ): void {
        $this->checkForm(
            $uri,
            'common.button_submit_add',
            $data,
            $userName,
            $method
        );
    }

    protected function checkDeleteEntity(
        string $uri,
        string $userName = self::ROLE_ADMIN,
        string $method = Request::METHOD_GET,
    ): void {
        $this->checkForm(
            $uri,
            'common.button_delete',
            [],
            $userName,
            $method
        );
    }

    protected function checkEditEntity(
        string $uri,
        array $data = [],
        string $userName = self::ROLE_ADMIN,
        string $method = Request::METHOD_POST,
        string $id = 'common.button_submit_edit',
    ): void {
        $this->checkForm(
            $uri,
            $id,
            $data,
            $userName,
            $method
        );
    }

    /**
     * @phpstan-param class-string $className
     */
    protected function checkUriWithEmptyEntity(
        string $uri,
        string $className,
        string $userName = self::ROLE_ADMIN,
        string $method = Request::METHOD_GET,
        int $expected = Response::HTTP_NOT_FOUND
    ): void {
        $this->loginUsername($userName);
        $this->deleteEntitiesByClass($className);
        $this->client->request($method, $uri);
        $this->checkResponse($uri, $userName, $expected);
    }
}
