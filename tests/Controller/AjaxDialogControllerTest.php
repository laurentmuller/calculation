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

use App\Utils\StringUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AjaxDialogControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Generator
    {
        $routes = ['page', 'item', 'task'];
        $users = [self::ROLE_USER, self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN];

        foreach ($routes as $route) {
            foreach ($users as $user) {
                yield ['/ajax/dialog/' . $route, $user, Response::HTTP_OK, Request::METHOD_GET, true];
            }
        }
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
            url: '/ajax/dialog/sort',
            username: self::ROLE_USER,
            method: Request::METHOD_POST,
            xmlHttpRequest: true,
            content: StringUtils::encodeJson($parameters)
        );
    }
}
