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

class WebpCommandTest extends CommandTestCase
{
    private const COMMAND_NAME = 'app:update-images';

    public function testExecuteDryRunNoImage(): void
    {
        $input = [
            'source' => '/',
            '--dry-run' => true,
        ];
        $output = $this->execute($input);
        self::assertOutputContainsString($output, 'No image found in directory');
    }

    public function testExecuteDrySuccess(): void
    {
        $input = [
            'source' => '/tests/files/public/images/users',
            '--dry-run' => true,
        ];
        $output = $this->execute($input);
        self::assertOutputContainsString(
            $output,
            'Conversion: 1',
            'Error: 0',
            'Skip: 0'
        );
    }

    public function testExecuteImageInvalid(): void
    {
        $name = 'example_invalid.png';
        $path = FileUtils::tempDir(__DIR__);
        self::assertIsString($path);
        $source = FileUtils::buildPath(__DIR__, '/../files/images', $name);
        $target = FileUtils::buildPath($path, $name);
        self::assertTrue(FileUtils::copy($source, $target));
        $source = FileUtils::makePathRelative($path, __DIR__ . '/../..');
        $input = ['source' => $source];
        $output = $this->execute($input);
        self::assertOutputContainsString(
            $output,
            'Conversion: 0',
            'Error: 0',
            'Skip: 0'
        );
    }

    public function testExecuteInvalidLevel(): void
    {
        $input = [
            'source' => '/',
            '--level' => -1,
            '--dry-run' => true,
        ];
        $output = $this->executeInvalid($input);
        self::assertOutputContainsString($output, 'The level argument must be greater than or equal to 0');
    }

    public function testExecuteInvalidPath(): void
    {
        $input = ['source' => '/fake/fake/fake'];
        $output = $this->executeInvalid($input);
        self::assertOutputContainsString($output, 'Unable to find the source directory');
    }

    public function testExecuteIsNotDirectory(): void
    {
        $input = ['source' => '/tests/bootstrap.php'];
        $output = $this->executeInvalid($input);
        self::assertOutputContainsString(
            $output,
            'The source',
            'is not a directory'
        );
    }

    public function testExecuteMissingSource(): void
    {
        $output = $this->executeInvalid();
        self::assertOutputContainsString($output, 'The "--source" argument requires a non-empty value.');
    }

    public function testExecuteSuccess(): void
    {
        $name = 'example.png';
        $path = FileUtils::tempDir(__DIR__);
        self::assertIsString($path);
        $source = FileUtils::buildPath(__DIR__, '/../files/images', $name);
        $target = FileUtils::buildPath($path, $name);
        self::assertTrue(FileUtils::copy($source, $target));
        $source = FileUtils::makePathRelative($path, __DIR__ . '/../..');
        $input = ['source' => $source];
        $output = $this->execute($input);
        self::assertOutputContainsString(
            $output,
            'Conversion: 1',
            'Error: 0',
            'Skip: 0'
        );
    }

    #[\Override]
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }
}
