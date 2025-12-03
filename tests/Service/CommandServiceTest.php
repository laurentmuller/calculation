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

namespace App\Tests\Service;

use App\Service\CommandService;
use App\Tests\KernelServiceTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;

final class CommandServiceTest extends KernelServiceTestCase
{
    public function testCount(): void
    {
        $actual = $this->getCommandService()->count();
        self::assertGreaterThan(0, $actual);
    }

    /**
     * @throws \Exception
     */
    public function testExecuteFailure(): void
    {
        $service = $this->getCommandService();
        $actual = $service->execute('fake_command_name');
        self::assertFalse($actual->isSuccess());
        self::assertSame(Command::FAILURE, $actual->status);
    }

    /**
     * @throws \Exception
     */
    public function testExecuteSuccess(): void
    {
        $service = $this->getCommandService();
        $actual = $service->execute('about');
        self::assertTrue($actual->isSuccess());
        self::assertSame(Command::SUCCESS, $actual->status);
        self::assertNotEmpty($actual->content);
        self::assertStringContainsString(Kernel::VERSION, $actual->content);
        self::assertStringContainsString(Kernel::END_OF_LIFE, $actual->content);
        self::assertStringContainsString(Kernel::END_OF_MAINTENANCE, $actual->content);
    }

    public function testFirst(): void
    {
        $service = $this->getCommandService();
        $command = $service->first();
        self::assertSame('about', $command['name']);
    }

    public function testGetCommandInvalid(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Unable to find the command "_fake_command_test".');
        $service = $this->getCommandService();
        $service->getCommand('_fake_command_test');
    }

    public function testGetCommands(): void
    {
        $service = $this->getCommandService();
        $commands = $service->getCommands();
        self::assertNotEmpty($commands);
        self::assertArrayHasKey('about', $commands);
        self::assertArrayNotHasKey('_fake_command_test', $commands);
    }

    public function testGetCommandValid(): void
    {
        $service = $this->getCommandService();
        $actual = $service->getCommand('about');
        self::assertSame('about', $actual['name']);
    }

    public function testGetGroupeCommands(): void
    {
        $service = $this->getCommandService();

        $groups = $service->getGroupedCommands();
        self::assertArrayHasKey('app', $groups);
        self::assertArrayHasKey(CommandService::GLOBAL_GROUP, $groups);
        self::assertArrayNotHasKey('_fake_command_group', $groups);

        $groups = $service->getGroupedCommands('MyGroup');
        self::assertArrayHasKey('app', $groups);
        self::assertArrayHasKey('MyGroup', $groups);
        self::assertArrayNotHasKey('_fake_command_group', $groups);
    }

    public function testGetGroupedNames(): void
    {
        $service = $this->getCommandService();

        $groups = $service->getGroupedNames();
        self::assertArrayHasKey('app', $groups);
        self::assertArrayHasKey(CommandService::GLOBAL_GROUP, $groups);
        self::assertArrayNotHasKey('_fake_command_group', $groups);

        $groups = $service->getGroupedNames('MyGroup');
        self::assertArrayHasKey('app', $groups);
        self::assertArrayHasKey('MyGroup', $groups);
        self::assertArrayNotHasKey('_fake_command_group', $groups);
    }

    public function testHasCommand(): void
    {
        $service = $this->getCommandService();
        self::assertTrue($service->hasCommand('about'));
        self::assertFalse($service->hasCommand('_fake_command_test'));
    }

    private function getCommandService(): CommandService
    {
        $kernel = $this->getService(KernelInterface::class);
        $cache = new ArrayAdapter();

        return new CommandService($kernel, $cache);
    }
}
