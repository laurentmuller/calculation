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

use App\Model\CommandResult;
use App\Service\CommandService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpFoundation\Response;

final class AdminDumSqlControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/admin/dump-sql', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/admin/dump-sql', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
        yield ['/admin/dump-sql', self::ROLE_SUPER_ADMIN];
    }

    public function testDumSqlFailure(): void
    {
        $result = new CommandResult(Command::FAILURE, 'Fake output');
        $service = $this->createMock(CommandService::class);
        $service->method('execute')
            ->willReturn($result);
        $this->setService(CommandService::class, $service);

        $this->checkRoute(
            url: 'admin/dump-sql',
            username: self::ROLE_SUPER_ADMIN,
            expected: Response::HTTP_FOUND
        );
    }

    public function testDumSqlOK(): void
    {
        $result = new CommandResult(Command::SUCCESS, '[OK]');
        $service = $this->createMock(CommandService::class);
        $service->method('execute')
            ->willReturn($result);
        $this->setService(CommandService::class, $service);

        $this->checkRoute(
            url: 'admin/dump-sql',
            username: self::ROLE_SUPER_ADMIN,
            expected: Response::HTTP_FOUND
        );
    }
}
