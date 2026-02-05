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

namespace App\Tests\Command;

use App\Utils\FileUtils;
use App\Utils\StringUtils;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

abstract class CommandTestCase extends KernelTestCase
{
    private const array OUTPUT_REPLACE = [
        '/\n/' => '',
        '/\s+/' => ' ',
    ];

    #[\Override]
    public static function setUpBeforeClass(): void
    {
        \putenv('COLUMNS=360');
    }

    protected static function assertOutputContainsString(string $actual, string ...$expected): void
    {
        $actual = StringUtils::pregReplaceAll(self::OUTPUT_REPLACE, $actual);
        foreach ($expected as $value) {
            self::assertStringContainsString($value, $actual);
        }
    }

    protected function createTempDirectory(): string
    {
        $path = FileUtils::tempDir(__DIR__);
        self::assertIsString($path);

        return $path;
    }

    protected function execute(array $input = [], array $options = [], int $statusCode = Command::SUCCESS): string
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $name = $this->getCommandName();
        $command = $application->get($name);
        $tester = new CommandTester($command);
        $result = $tester->execute($input, $options);
        self::assertSame($statusCode, $result);

        return $tester->getDisplay(true);
    }

    protected function executeFailure(array $input = [], array $options = []): string
    {
        return $this->execute($input, $options, Command::FAILURE);
    }

    protected function executeInvalid(array $input = [], array $options = []): string
    {
        return $this->execute($input, $options, Command::INVALID);
    }

    abstract protected function getCommandName(): string;
}
