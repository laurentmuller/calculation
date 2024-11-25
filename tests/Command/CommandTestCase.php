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

use App\Utils\StringUtils;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\MissingInputException;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class CommandTestCase extends KernelTestCase
{
    private const OUTPUT_REPLACE = [
        '/\r|\n/' => '',
        '/\s+/' => ' ',
    ];

    protected function execute(
        string $name,
        array $input = [],
        array $options = [],
        int $statusCode = Command::SUCCESS
    ): string {
        $kernel = self::bootKernel();
        $command = $this->findCommand($kernel, $name);
        $tester = new CommandTester($command);
        $result = $tester->execute($input, $options);
        self::assertSame($statusCode, $result);

        return $tester->getDisplay();
    }

    protected function executeMissingInput(string $name, array $input = []): void
    {
        self::expectException(MissingInputException::class);
        $this->execute($name, $input);
    }

    protected function findCommand(KernelInterface $kernel, string $name): Command
    {
        $application = new Application($kernel);

        return $application->find($name);
    }

    /**
     * @psalm-param string|string[] $expected
     */
    protected function validate(string $output, string|array $expected): void
    {
        $expected = (array) $expected;
        $output = StringUtils::pregReplaceAll(self::OUTPUT_REPLACE, $output);
        foreach ($expected as $value) {
            self::assertStringContainsString($value, $output);
        }
    }
}
