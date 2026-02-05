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
use Symfony\Component\Filesystem\Path;

final class TwigSpaceCommandTest extends CommandTestCase
{
    private const string COMMAND_NAME = 'app:twig-space';

    public function testDryRunWithChange(): void
    {
        $path = $this->copyTemplate('invalid_template.html.twig');
        $input = [
            'path' => $path,
            '--dry-run' => true,
        ];
        $output = $this->execute($input);
        self::assertOutputContainsString(
            $output,
            '[OK]',
            'Find consecutive spaces:',
            'Simulate updated 1 template(s) successfully',
            'Line',
            '··',
            \basename($path)
        );
    }

    public function testDryRunWithoutChange(): void
    {
        $path = $this->copyTemplate('valid_template.html.twig');
        $input = [
            'path' => $path,
            '--dry-run' => true,
        ];
        $output = $this->execute($input);
        self::assertOutputContainsString(
            $output,
            '[OK]',
            'Find consecutive spaces:',
            'No template updated',
            \basename($path)
        );
    }

    public function testFullPathIsNotDirectory(): void
    {
        $path = __FILE__;
        $input = [
            'path' => $path,
        ];
        $output = $this->executeInvalid($input);
        self::assertOutputContainsString(
            $output,
            '[ERROR]',
            'The template path',
            'is not a directory',
            $path
        );
    }

    public function testFullPathNotExist(): void
    {
        $path = 'fake_path';
        $input = [
            'path' => $path,
        ];
        $output = $this->executeInvalid($input);
        self::assertOutputContainsString(
            $output,
            '[ERROR]',
            'Unable to find the template path',
            $path
        );
    }

    public function testSuccessWithChange(): void
    {
        $path = $this->copyTemplate('invalid_template.html.twig');
        $input = [
            'path' => $path,
        ];
        $output = $this->execute($input);
        self::assertOutputContainsString(
            $output,
            '[OK]',
            'Updated 1 template(s) successfully',
            \basename($path)
        );
    }

    public function testSuccessWithoutChange(): void
    {
        $path = $this->copyTemplate('valid_template.html.twig');
        $input = [
            'path' => $path,
        ];
        $output = $this->execute($input);
        self::assertOutputContainsString(
            $output,
            '[OK]',
            'No template updated',
            \basename($path)
        );
    }

    #[\Override]
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    private function copyTemplate(string $template): string
    {
        $tempDirectory = $this->createTempDirectory();
        $originFile = Path::join(__DIR__, '/../files/twig/', $template);
        $targetFile = Path::join($tempDirectory, $template);
        self::assertTrue(FileUtils::copy($originFile, $targetFile, true));

        return $tempDirectory;
    }
}
