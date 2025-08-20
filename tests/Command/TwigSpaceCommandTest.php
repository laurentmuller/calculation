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

    public function testDryRunWithChange(): void
    {
        $path = $this->copyTemplate('invalid_template.html.twig');
        $input = [
            'path' => $path,
            '--dry-run' => true,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input);
        self::assertOutputContainsString(
            $output,
            '[OK]',
            'Simulate updated 1 template(s) successfully',
            'Line',
            '··',
            $path
        );
    }

    public function testDryRunWithoutChange(): void
    {
        $path = $this->copyTemplate('valid_template.html.twig');
        $input = [
            'path' => $path,
            '--dry-run' => true,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input);
        self::assertOutputContainsString(
            $output,
            '[OK]',
            'No template updated',
            $path
        );
    }

    public function testFullPathInvalid(): void
    {
        $input = [
            'path' => 'fake_path',
        ];
        $output = $this->execute(
            name: self::COMMAND_NAME,
            input: $input,
            statusCode: Command::INVALID
        );
        self::assertOutputContainsString(
            $output,
            '[ERROR]',
            'Unable to find the template path',
            'fake_path'
        );
    }

    public function testPathEmpty(): void
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
            '[ERROR]',
            'The templates path can no be empty.'
        );
    }

    public function testPathInvalid(): void
    {
        $path = FileUtils::buildPath('tests/Command', \basename(__FILE__));
        $input = [
            'path' => $path,
        ];
        $output = $this->execute(
            name: self::COMMAND_NAME,
            input: $input,
            statusCode: Command::INVALID
        );
        self::assertOutputContainsString(
            $output,
            '[ERROR]',
            'The template path',
            'is not a directory',
            $path
        );
    }

    public function testSuccessWithChange(): void
    {
        $path = $this->copyTemplate('invalid_template.html.twig');
        $input = [
            'path' => $path,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input);
        self::assertOutputContainsString(
            $output,
            '[OK]',
            'Updated 1 template(s) successfully',
            $path
        );
    }

    public function testSuccessWithoutChange(): void
    {
        $path = $this->copyTemplate('valid_template.html.twig');
        $input = [
            'path' => $path,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input);
        self::assertOutputContainsString(
            $output,
            '[OK]',
            'No template updated',
            $path
        );
    }

    private function copyTemplate(string $template): string
    {
        $dir = FileUtils::tempDir(__DIR__);
        self::assertIsString($dir);

        $originFile = FileUtils::buildPath(__DIR__, '/../files/twig/', $template);
        $targetFile = FileUtils::buildPath($dir, $template);
        self::assertTrue(FileUtils::copy($originFile, $targetFile, true));

        return FileUtils::buildPath('tests/Command/', FileUtils::makePathRelative($dir, __DIR__));
    }
}
