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

class TwigSpaceCommandTest extends CommandTestCase
{
    private const COMMAND_NAME = 'app:twig-space';

    public function testDryRunWithUpdate(): void
    {
        $input = [
            'path' => 'tests/files/twig',
            '--dry-run' => true,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input);
        self::assertOutputContainsString(
            $output,
            'Simulate updated'
        );
    }

    public function testEmptyPath(): void
    {
        $input = [
            'path' => '',
        ];
        $output = $this->execute(
            name: self::COMMAND_NAME,
            input: $input,
            statusCode: Command::INVALID
        );
        self::assertOutputContainsString(
            $output,
            'The templates path can no be empty.'
        );
    }

    public function testExecuteDrySuccess(): void
    {
        $input = [
            '--dry-run' => true,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input);
        self::assertOutputContainsString(
            $output,
            'No template updated from directory'
        );
    }

    public function testFullPathNotExist(): void
    {
        $input = [
            'path' => 'fake',
        ];
        $output = $this->execute(
            name: self::COMMAND_NAME,
            input: $input,
            statusCode: Command::INVALID
        );
        self::assertOutputContainsString(
            $output,
            'Unable to find the template path'
        );
    }

    public function testIsNotDir(): void
    {
        $input = [
            'path' => 'tests/Command/' . \basename(__FILE__),
        ];
        $output = $this->execute(
            name: self::COMMAND_NAME,
            input: $input,
            statusCode: Command::INVALID
        );
        self::assertOutputContainsString(
            $output,
            'The template path',
            'is not a directory'
        );
    }

    public function testRunSuccess(): void
    {
        $dir = FileUtils::tempDir(__DIR__);
        self::assertIsString($dir);

        $template = 'invalid_template.html.twig';
        $originFile = __DIR__ . '/../files/twig/' . $template;
        $targetFile = $dir . '/' . $template;
        self::assertTrue(FileUtils::copy($originFile, $targetFile, true));

        $path = 'tests/Command/' . FileUtils::makePathRelative($dir, __DIR__);
        $input = [
            'path' => $path,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input);
        self::assertOutputContainsString(
            $output,
            'Updated',
            'successfully'
        );
    }
}
