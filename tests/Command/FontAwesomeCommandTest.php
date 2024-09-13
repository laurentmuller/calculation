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

    public function testSimulate(): void
    {
        try {
            $input = [
                'source' => 'tests/Data/images/solid',
                'target' => '/tests/Command/temp',
                '--dry-run' => true,
            ];
            $output = $this->execute(
                name: self::COMMAND_NAME,
                input: $input,
            );
            $this->validate($output, 'Simulate copied 1 files successfully.');
        } finally {
            FileUtils::remove(__DIR__ . '/temp');
        }
    }

    public function testSourceEmpty(): void
    {
        $input = ['source' => 'translations'];
        $output = $this->execute(
            name: self::COMMAND_NAME,
            input: $input,
        );
        $this->validate($output, 'No image found:');
    }

    public function testSourceInvalid(): void
    {
        $input = ['source' => 'fake'];
        $output = $this->execute(
            name: self::COMMAND_NAME,
            input: $input,
            statusCode: Command::INVALID
        );
        $this->validate($output, 'Unable to find the SVG directory:');
    }

    public function testSuccess(): void
    {
        try {
            $input = [
                'source' => 'tests/Data/images/solid',
                'target' => '/tests/Command/temp',
            ];
            $output = $this->execute(
                name: self::COMMAND_NAME,
                input: $input,
            );
            $this->validate($output, 'Copied 1 files successfully.');
        } finally {
            FileUtils::remove(__DIR__ . '/temp');
        }
    }
}
