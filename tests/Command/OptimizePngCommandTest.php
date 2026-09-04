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

use Symfony\Component\Process\ExecutableFinder;

final class OptimizePngCommandTest extends CommandTestCase
{
    private const string COMMAND_NAME = 'app:optimize-png';

    public function testDryRun(): void
    {
        $input = [
            'source' => __DIR__ . '/../files/public/images/users',
            '--binary' => $this->getBinary(),
            '--dry-run' => true,
        ];
        $output = $this->execute($input);
        self::assertOutputContainsString(
            $output,
            'Dry-run mode enabled (no change applied).'
        );
    }

    public function testEmptyDirectory(): void
    {
        $input = [
            'source' => __DIR__,
            '--binary' => $this->getBinary(),
        ];
        $output = $this->execute($input);
        self::assertOutputContainsString(
            $output,
            'No PNG image found in directory "' . __DIR__ . '".'
        );
    }

    public function testInvalidBinary(): void
    {
        $input = [
            'source' => __DIR__,
            '--binary' => __FILE__,
        ];
        $output = $this->executeInvalid($input);
        self::assertOutputContainsString(
            $output,
            'The optipng binary "' . __FILE__ . '" is not executable.'
        );
    }

    public function testInvalidDirectory(): void
    {
        $input = [
            'source' => __FILE__,
        ];
        $output = $this->executeInvalid($input);
        self::assertOutputContainsString(
            $output,
            'The source "' . __FILE__ . '" is not a directory.'
        );
    }

    public function testInvalidLevel(): void
    {
        $input = [
            'source' => __DIR__,
            '--level' => 8,
        ];
        $output = $this->executeInvalid($input);
        self::assertOutputContainsString(
            $output,
            'The optimization level must be between 0 and 7, "8" given.'
        );
    }

    #[\Override]
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    private function getBinary(): ?string
    {
        $finder = new ExecutableFinder();
        if ('\\' === \DIRECTORY_SEPARATOR) {
            return $finder->find('cmd.exe');
        }

        return $finder->find('ls');
    }
}
