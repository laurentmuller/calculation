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

use Symfony\Component\Console\Command\Command;

class HeaderNameCommandTest extends CommandTestCase
{
    private const COMMAND_NAME = 'app:header:name';
    private const DATA_PATH = '/tests/files/css';

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
        $expected = [
            self::DATA_PATH,
            'Simulate',
            'Skipped 2',
            'Updated 2',
        ];
        $input = [
            '--dry-run' => true,
            '--depth' => 0,
            'path' => self::DATA_PATH,
        ];

        $output = $this->execute(self::COMMAND_NAME, $input);
        $this->validate($output, $expected);
    }

    public function testDryRun(): void
    {
        $expected = [
            self::DATA_PATH,
            'Simulate',
            'Skipped 2',
            'Updated 2',
        ];
        $input = [
            '--dry-run' => true,
            'path' => self::DATA_PATH,
        ];

        $output = $this->execute(self::COMMAND_NAME, $input);
        $this->validate($output, $expected);
    }

    public function testInvalidPattern(): void
    {
        $expected = [
            'Invalid patterns:',
            'Allowed values:',
            '"css"',
            '"js"',
            '"twig"',
            '"css_invalid"',
            '"js_invalid"',
        ];
        $input = [
            '--pattern' => ['css_invalid', 'js_invalid'],
            'path' => self::DATA_PATH,
        ];

        $output = $this->execute(self::COMMAND_NAME, $input, [], Command::INVALID);
        $this->validate($output, $expected);
    }

    public function testNoPattern(): void
    {
        $expected = [
            'No pattern defined.',
            'Allowed values:',
            '"css"',
            '"js"',
            '"twig"',
        ];
        $input = [
            '--pattern' => [],
            'path' => self::DATA_PATH,
        ];

        $output = $this->execute(self::COMMAND_NAME, $input, [], Command::INVALID);
        $this->validate($output, $expected);
    }

    public function testNoResult(): void
    {
        $expected = [
            self::DATA_PATH,
            'No file found in directory',
        ];
        $input = [
            '--pattern' => 'js',
            '--dry-run' => true,
            'path' => self::DATA_PATH,
        ];

        $output = $this->execute(self::COMMAND_NAME, $input);
        $this->validate($output, $expected);
    }

    public function testSetContent(): void
    {
        $expected = [
            self::DATA_PATH,
            'Skipped 2',
            'Updated 2',
            'tests/files/css/no_header.css',
            'tests/files/css/old_header.css',
        ];
        $input = [
            '--pattern' => 'css',
            'path' => self::DATA_PATH,
        ];

        $output = $this->execute(self::COMMAND_NAME, $input);
        $this->validate($output, $expected);
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
