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

class FontAwesomeCommandTest extends CommandTestCase
{
    private const COMMAND_NAME = 'app:fontawesome';

    public function testInvalidJson(): void
    {
        $input = ['source' => 'tests/files/json/fontawesome_invalid.json'];
        $output = $this->execute(
            name: self::COMMAND_NAME,
            input: $input,
            statusCode: Command::FAILURE
        );
        self::assertOutputContainsString(
            $output,
            'Unable to get content of the JSON source file:',
            'fontawesome_invalid.json'
        );
    }

    public function testRawEmpty(): void
    {
        try {
            $input = [
                'source' => 'tests/files/json/fontawesome_raw_empty.json',
                'target' => '/tests/Command/temp',
            ];
            $output = $this->execute(
                name: self::COMMAND_NAME,
                input: $input,
            );
            self::assertOutputContainsString(
                $output,
                'Generate images successfully: 2 files from 1 sources.',
                'fontawesome_raw_empty.json'
            );
        } finally {
            FileUtils::remove(__DIR__ . '/temp');
        }
    }

    public function testSimulate(): void
    {
        try {
            $input = [
                'source' => 'tests/files/json/fontawesome_valid.json',
                'target' => '/tests/Command/temp',
                '--dry-run' => true,
            ];
            $output = $this->execute(
                name: self::COMMAND_NAME,
                input: $input,
            );
            self::assertOutputContainsString(
                $output,
                'Simulate command successfully: 2 files from 1 sources.',
                'fontawesome_valid.json'
            );
        } finally {
            FileUtils::remove(__DIR__ . '/temp');
        }
    }

    public function testSourceEmpty(): void
    {
        $input = ['source' => 'tests/files/json/fontawesome_empty.json'];
        $output = $this->execute(
            name: self::COMMAND_NAME,
            input: $input,
            statusCode: Command::FAILURE
        );
        self::assertOutputContainsString(
            $output,
            'No image found:',
            'fontawesome_empty.json'
        );
    }

    public function testSourceInvalid(): void
    {
        $input = ['source' => 'fake'];
        $output = $this->execute(
            name: self::COMMAND_NAME,
            input: $input,
            statusCode: Command::FAILURE
        );
        self::assertOutputContainsString(
            $output,
            'Unable to find JSON source file:',
            'fake'
        );
    }

    public function testSuccess(): void
    {
        try {
            $input = [
                'source' => 'tests/files/json/fontawesome_valid.json',
                'target' => '/tests/Command/temp',
            ];
            $output = $this->execute(
                name: self::COMMAND_NAME,
                input: $input,
            );
            self::assertOutputContainsString(
                $output,
                'Generate images successfully: 2 files from 1 sources.',
                'fontawesome_valid.json'
            );
        } finally {
            FileUtils::remove(__DIR__ . '/temp');
        }
    }
}
