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

use App\Command\UpdateAssetsCommand;
use App\Service\EnvironmentService;
use App\Utils\FileUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

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
        if (!FileUtils::exists($dir) && !FileUtils::mkdir($dir)) {
            self::fail('Unable to create directory ' . $dir);
        }
    }

    private function dumpFile(string $file, string $content): void
    {
        if (!FileUtils::dumpFile($file, $content)) {
            self::fail('Unable to set content to ' . $file);
        }
    }

    private function executeInvalidCommand(string $projectDir): void
    {
        $service = new EnvironmentService('test');
        $command = new UpdateAssetsCommand($projectDir, $service);
        $tester = new CommandTester($command);
        $result = $tester->execute([]);
        self::assertSame(Command::INVALID, $result);
    }

    private function removeFile(string $file): void
    {
        if (FileUtils::exists($file) && !FileUtils::remove($file)) {
            self::fail('Unable to delete file ' . $file);
        }
    }
}
