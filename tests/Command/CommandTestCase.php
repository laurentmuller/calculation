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

abstract class CommandTestCase extends KernelTestCase
{
    private const OUTPUT_REPLACE = [
        '/\r|\n/' => '',
        '/\s+/' => ' ',
    ];

    /**
     * @phpstan-param string|string[] $expected
     */
    protected static function assertOutputContainsString(string|array $expected, string $actual): void
    {
        $expected = (array) $expected;
        $actual = StringUtils::pregReplaceAll(self::OUTPUT_REPLACE, $actual);
        foreach ($expected as $value) {
            self::assertStringContainsString($value, $actual);
        }
    }

    protected function execute(
        string $name,
        array $input = [],
        array $options = [],
        int $statusCode = Command::SUCCESS
    ): string {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $command = $application->find($name);
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
}
