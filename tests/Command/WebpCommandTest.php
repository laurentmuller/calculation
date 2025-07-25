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
use Symfony\Component\Console\Command\Command;

class WebpCommandTest extends CommandTestCase
{
    private const COMMAND_NAME = 'app:update-images';

    public function testExecuteDryRunNoImage(): void
    {
        $expected = 'No image found in directory';
        $input = [
            'source' => '/',
            '--dry-run' => true,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input);
        self::assertOutputContainsString($expected, $output);
    }

    public function testExecuteDrySuccess(): void
    {
        $expected = [
            'Conversion: 1',
            'Error: 0',
            'Skip: 0',
        ];
        $input = [
            'source' => '/tests/files/public/images/users',
            '--dry-run' => true,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input);
        self::assertOutputContainsString($expected, $output);
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

        $expected = [
            'Conversion: 0',
            'Error: 0',
            'Skip: 0',
        ];
        $input = ['source' => $source];
        $output = $this->execute(self::COMMAND_NAME, $input);
        self::assertOutputContainsString($expected, $output);
    }

    public function testExecuteInvalidLevel(): void
    {
        $expected = 'The level argument must be greater than or equal to 0';
        $input = [
            'source' => '/',
            '--level' => -1,
            '--dry-run' => true,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input, statusCode: Command::INVALID);
        self::assertOutputContainsString($expected, $output);
    }

    public function testExecuteInvalidPath(): void
    {
        $expected = 'Unable to find the source directory';
        $input = ['source' => '/fake/fake/fake'];
        $output = $this->execute(self::COMMAND_NAME, $input, statusCode: Command::INVALID);
        self::assertOutputContainsString($expected, $output);
    }

    public function testExecuteIsNotDirectory(): void
    {
        $expected = [
            'The source',
            'is not a directory',
        ];
        $input = ['source' => '/tests/bootstrap.php'];
        $output = $this->execute(self::COMMAND_NAME, $input, statusCode: Command::INVALID);
        self::assertOutputContainsString($expected, $output);
    }

    public function testExecuteMissingSource(): void
    {
        $expected = 'The "--source" argument requires a non-empty value.';
        $output = $this->execute(self::COMMAND_NAME, statusCode: Command::INVALID);
        self::assertOutputContainsString($expected, $output);
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

        $expected = [
            'Conversion: 1',
            'Error: 0',
            'Skip: 0',
        ];
        $input = ['source' => $source];
        $output = $this->execute(self::COMMAND_NAME, $input);
        self::assertOutputContainsString($expected, $output);
    }
}
