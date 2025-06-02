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

class UpdateAssetsCommandTest extends CommandTestCase
{
    private const COMMAND_NAME = 'app:update-assets';

    public function testEmptyConfigurationFile(): void
    {
        $projectDir = FileUtils::tempDir();
        self::assertIsString($projectDir);
        $publicDir = FileUtils::buildPath($projectDir, 'public');
        $this->createDirectory($publicDir);
        $configurationFile = FileUtils::buildPath($publicDir, 'vendor.json');
        $this->dumpFile($configurationFile, '');

        try {
            $this->executeInvalidCommand($projectDir);
        } finally {
            $this->removeFile($configurationFile);
            $this->removeFile($publicDir);
        }
    }

    public function testExecuteDryRun(): void
    {
        $expected = [
            'Check versions:',
            'jquery',
            'bootstrap',
            'font-awesome',
            'jquery-validate',
            'highcharts',
            'html5sortable',
            'mark.js',
            'zxcvbn',
            'jquery-contextmenu',
            'clipboard.js',
            'bootstrap-table',
            'select2',
            'select2-bootstrap-5-theme',
            'currency-flags',
            'font-mfizz',
        ];
        $input = ['--dry-run' => true];
        $output = $this->execute(self::COMMAND_NAME, $input);
        $this->validate($output, $expected);
    }

    public function testExecuteUpdate(): void
    {
        $expected = [
            '[OK]',
            'Installed',
            'plugins',
            'files',
            'directory',
            '/public/vendor',
        ];
        $output = $this->execute(self::COMMAND_NAME);
        $this->validate($output, $expected);
    }

    public function testFalseConfigurationFile(): void
    {
        $projectDir = FileUtils::tempDir();
        self::assertIsString($projectDir);
        $publicDir = FileUtils::buildPath($projectDir, 'public');
        $this->createDirectory($publicDir);
        $configurationFile = FileUtils::buildPath($publicDir, 'vendor.json');
        $this->dumpFile($configurationFile, (string) \json_encode(false));

        try {
            $this->executeInvalidCommand($projectDir);
        } finally {
            $this->removeFile($configurationFile);
            $this->removeFile($publicDir);
        }
    }

    public function testInvalidContentConfigurationFile(): void
    {
        $projectDir = FileUtils::tempDir();
        self::assertIsString($projectDir);
        $publicDir = FileUtils::buildPath($projectDir, 'public');
        $this->createDirectory($publicDir);
        $configurationFile = FileUtils::buildPath($publicDir, 'vendor.json');
        $this->dumpFile($configurationFile, (string) \json_encode(['target' => '']));

        try {
            $this->executeInvalidCommand($projectDir);
        } finally {
            $this->removeFile($configurationFile);
            $this->removeFile($publicDir);
        }
    }

    public function testInvalidPublicDir(): void
    {
        $projectDir = __DIR__ . '/fake';
        $this->executeInvalidCommand($projectDir);
    }

    public function testNoConfigurationFile(): void
    {
        $projectDir = FileUtils::tempDir();
        self::assertIsString($projectDir);
        $publicDir = FileUtils::buildPath($projectDir, 'public');
        $this->createDirectory($publicDir);

        try {
            $this->executeInvalidCommand($projectDir);
        } finally {
            $this->removeFile($publicDir);
        }
    }

    private function createDirectory(string $dir): void
    {
        if (FileUtils::exists($dir)) {
            return;
        }
        $actual = FileUtils::mkdir($dir);
        self::assertTrue($actual);
    }

    private function dumpFile(string $file, string $content): void
    {
        $actual = FileUtils::dumpFile($file, $content);
        self::assertTrue($actual);
    }

    private function executeInvalidCommand(string $projectDir): void
    {
        $input = [
            '--directory' => $projectDir,
        ];
        $expected = Command::INVALID;
        $this->execute(
            name: self::COMMAND_NAME,
            input: $input,
            statusCode: $expected
        );
    }

    private function removeFile(string $file): void
    {
        if (!FileUtils::exists($file)) {
            return;
        }
        $actual = FileUtils::remove($file);
        self::assertTrue($actual);
    }
}
