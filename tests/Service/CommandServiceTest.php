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
use App\Tests\ServiceTrait;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpKernel\Kernel;

#[\PHPUnit\Framework\Attributes\CoversClass(CommandService::class)]
class CommandServiceTest extends KernelTestCase
{
    use ServiceTrait;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public static function getExecuteReplace(): \Iterator
    {
        yield [false];
        yield [true];
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testCount(): void
    {
        $count = $this->getCommandService()->count();
        self::assertGreaterThan(0, $count);
    }

    /**
     * @throws \Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getExecuteReplace')]
    public function testExecute(bool $replaceResult): void
    {
        $service = $this->getCommandService();
        $actual = $service->execute('about', [], $replaceResult);
        self::assertTrue($actual->isSuccess());
        self::assertSame(Command::SUCCESS, $actual->status);
        self::assertNotEmpty($actual->content);
        self::assertStringContainsString(Kernel::VERSION, $actual->content);
        self::assertStringContainsString(Kernel::END_OF_LIFE, $actual->content);
        self::assertStringContainsString(Kernel::END_OF_MAINTENANCE, $actual->content);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testFirst(): void
    {
        $service = $this->getCommandService();
        $command = $service->first();
        self::assertIsArray($command);
        self::assertSame('about', $command['name']);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testGetCommand(): void
    {
        $service = $this->getCommandService();
        self::assertIsArray($service->getCommand('about'));
        self::assertIsNotArray($service->getCommand('_fake_command_test'));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testGetCommands(): void
    {
        $service = $this->getCommandService();
        $commands = $service->getCommands();
        self::assertNotEmpty($commands);
        self::assertArrayHasKey('about', $commands);
        self::assertArrayNotHasKey('_fake_command_test', $commands);
    }

    /**
     * @throws InvalidArgumentException
     */
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

    /**
     * @throws InvalidArgumentException
     */
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

    /**
     * @throws InvalidArgumentException
     */
    public function testHasCommand(): void
    {
        $service = $this->getCommandService();
        self::assertTrue($service->hasCommand('about'));
        self::assertFalse($service->hasCommand('_fake_command_test'));
    }

    private function getCommandService(): CommandService
    {
        return $this->getService(CommandService::class);
    }
}
