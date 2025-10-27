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

final class FontAwesomeCommandTest extends CommandTestCase
{
    private const COMMAND_NAME = 'app:fontawesome';

    public function testInvalidJson(): void
    {
        $input = ['source' => 'tests/files/json/fontawesome_invalid.json'];
        $output = $this->executeFailure($input);
        self::assertOutputContainsString(
            $output,
            'Unable to get content of the JSON source file:',
            'fontawesome_invalid.json'
        );
    }

    public function testRawEmpty(): void
    {
        $input = [
            'source' => 'tests/files/json/fontawesome_raw_empty.json',
            'target' => $this->createTempDirectory(),
        ];
        $output = $this->execute($input);
        self::assertOutputContainsString(
            $output,
            'Generate images successfully: 2 files from 1 sources.',
            'fontawesome_raw_empty.json'
        );
    }

    public function testSimulate(): void
    {
        $input = [
            'source' => 'tests/files/json/fontawesome_valid.json',
            'target' => $this->createTempDirectory(),
            '--dry-run' => true,
        ];
        $output = $this->execute($input);
        self::assertOutputContainsString(
            $output,
            'Simulate command successfully: 2 files from 1 sources.',
            'fontawesome_valid.json'
        );
    }

    public function testSourceEmpty(): void
    {
        $input = ['source' => 'tests/files/json/fontawesome_empty.json'];
        $output = $this->executeFailure($input);
        self::assertOutputContainsString(
            $output,
            'No image found:',
            'fontawesome_empty.json'
        );
    }

    public function testSourceInvalid(): void
    {
        $input = ['source' => 'fake'];
        $output = $this->executeFailure($input);
        self::assertOutputContainsString(
            $output,
            'Unable to find JSON source file:',
            'fake'
        );
    }

    public function testSuccess(): void
    {
        $input = [
            'source' => 'tests/files/json/fontawesome_valid.json',
            'target' => $this->createTempDirectory(),
        ];
        $output = $this->execute($input);
        self::assertOutputContainsString(
            $output,
            'Generate images successfully: 2 files from 1 sources.',
            'fontawesome_valid.json'
        );
    }

    #[\Override]
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }
}
