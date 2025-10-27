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

use App\Service\CommandDataService;
use App\Service\CommandService;
use App\Tests\KernelServiceTestCase;

/**
 * @phpstan-import-type CommandType from CommandService
 */
final class CommandDataServiceTest extends KernelServiceTestCase
{
    public function testCreateData(): void
    {
        $command = $this->getCommand('completion');
        $dataService = $this->getCommandDataService();
        $actual = $dataService->createData($command);

        self::assertNotEmpty($actual);
        self::assertArrayHasKey('argument-shell', $actual);
        self::assertArrayHasKey('option-help', $actual);
    }

    public function testCreateParameters(): void
    {
        $value = 'my-shell';
        $command = $this->getCommand('completion');
        $dataService = $this->getCommandDataService();
        $actual = $dataService->createParameters($command, [
            'argument-shell' => $value,
            'option-help' => true,
        ]);

        self::assertNotEmpty($actual);
        self::assertArrayHasKey('shell', $actual);
        self::assertSame($value, $actual['shell']);
        self::assertArrayHasKey('--help', $actual);
        self::assertTrue($actual['--help']);

        $actual = $dataService->createParameters($command, [
            'option-help' => false,
        ]);
        self::assertEmpty($actual);

        self::expectException(\LogicException::class);
        $dataService->createParameters($command, [
            'fake-invalid-option' => true,
        ]);
    }

    public function testGetArgumentKey(): void
    {
        $actual = CommandDataService::getArgumentKey('fake');
        self::assertSame('argument-fake', $actual);
    }

    public function testGetOptionKey(): void
    {
        $actual = CommandDataService::getOptionKey('fake');
        self::assertSame('option-fake', $actual);
    }

    public function testInvalidArgument(): void
    {
        $argument = [
            'name' => 'fake',
            'is_required' => false,
            'is_array' => false,
            'description' => 'fake',
            'default' => true,
            'display' => 'fake',
            'arguments' => 'fake',
        ];
        $command = [
            'name' => 'fake',
            'description' => 'fake',
            'usage' => [],
            'help' => 'fake',
            'hidden' => false,
            'arguments' => ['fake' => $argument],
            'options' => [],
        ];
        $dataService = $this->getCommandDataService();
        self::expectException(\LogicException::class);
        $dataService->createParameters($command, [
            'argument-fake-invalid' => true,
        ]);
    }

    public function testInvalidOption(): void
    {
        $option = [
            'name' => 'fake',
            'shortcut' => 'fake',
            'name_shortcut' => 'fake',
            'accept_value' => false,
            'is_value_required' => false,
            'is_multiple' => false,
            'description' => 'fake',
            'default' => true,
            'display' => 'fake',
            'arguments' => 'fake',
        ];
        $command = [
            'name' => 'fake',
            'description' => 'fake',
            'usage' => [],
            'help' => 'fake',
            'hidden' => false,
            'arguments' => [],
            'options' => ['fake' => $option],
        ];
        $dataService = $this->getCommandDataService();
        self::expectException(\LogicException::class);
        $dataService->createParameters($command, [
            'option-fake-invalid' => true,
        ]);
    }

    public function testValidateData(): void
    {
        $command = $this->getCommand('completion');
        $dataService = $this->getCommandDataService();

        $actual = $dataService->validateData($command, []);
        self::assertEmpty($actual);

        $actual = $dataService->validateData($command, [
            'argument-fake-invalid' => true,
            'option-fake-invalid' => true,
        ]);
        self::assertEmpty($actual);

        $value = 'my-shell';
        $actual = $dataService->validateData($command, [
            'argument-shell' => $value,
            'option-help' => true,
        ]);
        self::assertNotEmpty($actual);
        self::assertArrayHasKey('argument-shell', $actual);
        self::assertSame($value, $actual['argument-shell']);
        self::assertArrayHasKey('option-help', $actual);
        self::assertTrue($actual['option-help']);
    }

    public function testValidateKeyNotFound(): void
    {
        $command = [
            'name' => 'fake',
            'description' => 'fake',
            'usage' => [],
            'help' => 'fake',
            'hidden' => false,
            'arguments' => [],
            'options' => [],
        ];
        $dataService = $this->getCommandDataService();
        $actual = $dataService->validateData($command, [
            'fake' => 'fake',
        ]);
        self::assertEmpty($actual);
    }

    /**
     * @phpstan-return CommandType
     */
    private function getCommand(string $name = 'about'): array
    {
        $commandService = $this->getCommandService();
        $command = $commandService->getCommand($name);
        self::assertIsArray($command);

        return $command;
    }

    private function getCommandDataService(): CommandDataService
    {
        return $this->getService(CommandDataService::class);
    }

    private function getCommandService(): CommandService
    {
        return $this->getService(CommandService::class);
    }
}
