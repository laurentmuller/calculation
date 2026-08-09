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
    private string $content = 'Some Change;10';
    private int $result = Command::SUCCESS;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $service = self::createStub(CommandService::class);
        $service->method('execute')
            ->willReturnCallback(fn (): CommandResult => new CommandResult($this->result, $this->content));
        $this->setService(CommandService::class, $service);
    }

    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/admin/dump-sql', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/admin/dump-sql', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
        yield ['/admin/dump-sql', self::ROLE_SUPER_ADMIN];
    }

    public function testDumSqlFailure(): void
    {
        $this->result = Command::FAILURE;
        $this->content = 'Fake output';
        $this->checkRoute(
            url: 'admin/dump-sql',
            username: self::ROLE_SUPER_ADMIN,
            expected: Response::HTTP_FOUND
        );
    }

    public function testDumSqlOK(): void
    {
        $this->result = Command::SUCCESS;
        $this->content = '[OK]';
        $this->checkRoute(
            url: 'admin/dump-sql',
            username: self::ROLE_SUPER_ADMIN,
            expected: Response::HTTP_FOUND
        );
    }

    public function testDumSqlWithChange(): void
    {
        $this->result = Command::SUCCESS;
        $this->content = 'Some Change;10';
        $this->checkRoute(
            url: 'admin/dump-sql',
            username: self::ROLE_SUPER_ADMIN,
        );
    }
}
