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
use App\Tests\ServiceTrait;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @psalm-import-type CommandType from CommandService
 */
#[\PHPUnit\Framework\Attributes\CoversClass(CommandDataService::class)]
class CommandDataServiceTest extends KernelTestCase
{
    use ServiceTrait;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testCreateData(): void
    {
        $command = $this->getCommand('completion');
        $dataService = $this->getCommandDataService();
        $actual = $dataService->createData($command);

        self::assertNotEmpty($actual);
        self::assertArrayHasKey('argument-shell', $actual);
        self::assertArrayHasKey('option-help', $actual);
    }

    /**
     * @throws InvalidArgumentException
     */
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
        $actual = $dataService->createParameters($command, [
            'fake-invalid-option' => true,
        ]);
        self::assertEmpty($actual);
    }

    public function testGetArgumentKey(): void
    {
        $dataService = $this->getCommandDataService();
        $actual = $dataService->getArgumentKey('fake');
        self::assertSame('argument-fake', $actual);
    }

    public function testGetOptionKey(): void
    {
        $dataService = $this->getCommandDataService();
        $actual = $dataService->getOptionKey('fake');
        self::assertSame('option-fake', $actual);
    }

    /**
     * @throws InvalidArgumentException
     */
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

    /**
     * @psalm-return CommandType
     *
     * @throws InvalidArgumentException
     *
     * @phpstan-ignore-next-line
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
