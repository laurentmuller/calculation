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
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UpdateAssetsCommand::class)]
class UpdateAssetsCommandTest extends CommandTestCase
{
    private const COMMAND_NAME = 'app:update-assets';

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
}
