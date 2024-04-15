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

use App\Controller\CommandController;
use App\Report\CommandsReport;
use App\Service\CommandDataService;
use App\Service\CommandFormService;
use App\Service\CommandService;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(CommandController::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(CommandsReport::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(CommandService::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(CommandDataService::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(CommandFormService::class)]
class CommandControllerTest extends AbstractControllerTestCase
{
    private const ROUTES = [
        '/command',
        '/command?name=about',
        '/command?name=list',
        '/command?name=lint:yaml',

        '/command/content?name=about',
        '/command/content?name=list',
        '/command/content?name=lint:yaml',

        '/command/execute?name=about',
        '/command/execute?name=list',
        '/command/execute?name=lint:yaml',

        '/command/pdf',
    ];

    public static function getRoutes(): \Generator
    {
        foreach (self::ROUTES as $route) {
            yield [$route, self::ROLE_USER, Response::HTTP_FORBIDDEN];
            yield [$route, self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
            yield [$route, self::ROLE_SUPER_ADMIN];
        }

        // command not found
        yield [
            '/command/content?name=fake_command_not_exist',
            self::ROLE_SUPER_ADMIN,
            Response::HTTP_NOT_FOUND];
        yield [
            '/command/execute?name=fake_command_not_exist',
            self::ROLE_SUPER_ADMIN,
            Response::HTTP_NOT_FOUND];
    }
}
