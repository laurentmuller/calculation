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

final class HeaderNameCommandTest extends CommandTestCase
{
    private const string COMMAND_NAME = 'app:header:name';
    private const string DATA_PATH = '/tests/files/css';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->replaceCssContents();
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->replaceCssContents();
    }

    public function testDepth(): void
    {
        $input = [
            '--dry-run' => true,
            '--depth' => 0,
            'path' => self::DATA_PATH,
        ];
        $output = $this->execute($input);
        self::assertOutputContainsString(
            $output,
            self::DATA_PATH,
            'Updated: 2',
            'Skipped: 2',
            'The update was simulated without changing the content of the files.'
        );
    }

    public function testDryRun(): void
    {
        $input = [
            '--dry-run' => true,
            'path' => self::DATA_PATH,
        ];
        $output = $this->execute($input);
        self::assertOutputContainsString(
            $output,
            self::DATA_PATH,
            'Updated: 2',
            'Skipped: 2',
            'The update was simulated without changing the content of the files.'
        );
    }

    public function testInvalidPattern(): void
    {
        $input = [
            '--patterns' => ['css_invalid', 'js_invalid'],
            'path' => self::DATA_PATH,
        ];
        $output = $this->executeInvalid($input);
        self::assertOutputContainsString(
            $output,
            'Invalid patterns:',
            'Allowed values:',
            '"css"',
            '"js"',
            '"twig"',
            '"css_invalid"',
            '"js_invalid"'
        );
    }

    public function testNoResult(): void
    {
        $input = [
            '--patterns' => ['js'],
            '--dry-run' => true,
            'path' => self::DATA_PATH,
        ];
        $output = $this->execute($input);
        self::assertOutputContainsString(
            $output,
            self::DATA_PATH,
            'No file found in directory'
        );
    }

    public function testSetContent(): void
    {
        $input = [
            '--patterns' => ['css'],
            'path' => self::DATA_PATH,
        ];
        $output = $this->execute($input);
        self::assertOutputContainsString(
            $output,
            self::DATA_PATH,
            'Updated: 2',
            'Skipped: 2',
            'tests/files/css/no_header.css',
            'tests/files/css/old_header.css'
        );
    }

    #[\Override]
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    private function replaceCssContents(): void
    {
        $file = __DIR__ . '/../files/css/no_header.css';
        if (\file_exists($file)) {
            \file_put_contents($file, "html {\n    width: 100vw;\n}\n");
        }
        $file = __DIR__ . '/../files/css/old_header.css';
        if (\file_exists($file)) {
            \file_put_contents($file, "/* old_header.css */\nhtml {\n    width: 100vw;\n}\n");
        }
    }
}
