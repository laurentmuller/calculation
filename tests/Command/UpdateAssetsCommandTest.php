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
use Symfony\Component\Console\Output\OutputInterface;

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

    public function testExecuteDryRunVerbose(): void
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
        $input = [
            '--dry-run' => true,
        ];
        $options = [
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input, $options);
        $this->validate($output, $expected);
    }

    public function testExecuteDryRunVeryVerbose(): void
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
        $input = [
            '--dry-run' => true,
        ];
        $options = [
            'verbosity' => OutputInterface::VERBOSITY_VERY_VERBOSE,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input, $options);
        $this->validate($output, $expected);
    }

    public function testExecuteVerbose(): void
    {
        $expected = [
            '[OK]',
            'Installed',
            'plugins',
            'files',
            'directory',
            '/public/vendor',
        ];
        $options = [
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
        ];
        $output = $this->execute(self::COMMAND_NAME, [], $options);
        $this->validate($output, $expected);
    }

    public function testExecuteVeryVerbose(): void
    {
        $expected = [
            '[OK]',
            'Installed',
            'plugins',
            'files',
            'directory',
            '/public/vendor',
        ];
        $options = [
            'verbosity' => OutputInterface::VERBOSITY_VERY_VERBOSE,
        ];
        $output = $this->execute(self::COMMAND_NAME, [], $options);
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

    public function testPluginDisabled(): void
    {
        $expected = [
            'Disabled',
            'jquery',
            '3.7.1',
        ];
        $input = [
            '--directory' => __DIR__ . '/../files/json',
            '--file' => 'vendor_disabled_plugin.json',
        ];
        $options = [
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input, $options);
        $this->validate($output, $expected);
    }

    public function testPluginEmptyFiles(): void
    {
        $expected = [
            'Skip',
            'jquery',
            '3.7.1',
            'No file defined',
        ];
        $input = [
            '--directory' => __DIR__ . '/../files/json',
            '--file' => 'vendor_empty_files.json',
        ];
        $options = [
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input, $options);
        $this->validate($output, $expected);
    }

    public function testPluginInvalidSource(): void
    {
        $expected = [
            'Unable to get source',
            'fake',
            'for the plugin',
            'jquery',
        ];
        $input = [
            '--directory' => __DIR__ . '/../files/json',
            '--file' => 'vendor_fake_source.json',
        ];
        $output = $this->execute(self::COMMAND_NAME, $input, [], Command::FAILURE);
        $this->validate($output, $expected);
    }

    public function testPluginNotUpdate(): void
    {
        $expected = [
            'Installed 1 plugins and 1 files',
        ];
        $input = [
            '--directory' => __DIR__ . '/../files/json',
            '--file' => 'vendor_not_update_plugin.json',
        ];
        $output = $this->execute(self::COMMAND_NAME, $input);
        $this->validate($output, $expected);
    }

    public function testPluginOldVersion(): void
    {
        $expected = [
            'The plugin',
            'jquery',
            'version',
            '3.7.0',
            'can be updated to the new version',
        ];
        $input = [
            '--directory' => __DIR__ . '/../files/json',
            '--file' => 'vendor_old_version.json',
        ];
        $output = $this->execute(self::COMMAND_NAME, $input);
        $this->validate($output, $expected);
    }

    public function testPluginOldVersionDryRun(): void
    {
        $expected = [
            'Check versions:✗',
            'jquery 3.7.0',
            'Version',
            'available',
        ];
        $input = [
            '--directory' => __DIR__ . '/../files/json',
            '--file' => 'vendor_old_version.json',
            '--dry-run' => true,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input);
        $this->validate($output, $expected);
    }

    public function testPluginWrongFile(): void
    {
        $expected = [
            'Unable to get content',
            'fake.js',
        ];
        $input = [
            '--directory' => __DIR__ . '/../files/json',
            '--file' => 'vendor_wrong_files.json',
        ];
        $output = $this->execute(self::COMMAND_NAME, $input, [], Command::FAILURE);
        $this->validate($output, $expected);
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
        $this->execute(
            name: self::COMMAND_NAME,
            input: $input,
            statusCode: Command::INVALID
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
