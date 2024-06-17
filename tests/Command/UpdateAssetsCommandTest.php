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

use App\Command\LoggerTrait;
use App\Command\UpdateAssetsCommand;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UpdateAssetsCommand::class)]
#[CoversClass(LoggerTrait::class)]
class UpdateAssetsCommandTest extends AbstractCommandTestCase
{
    private const COMMAND_NAME = 'app:update-assets';

    public function testExecuteDryRun(): void
    {
        $output = $this->execute(self::COMMAND_NAME, ['--dry-run' => true]);
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
        $this->validate($output, $expected);
    }

    public function testExecuteUpdate(): void
    {
        $output = $this->execute(self::COMMAND_NAME);
        $expected = [
            '[OK]',
            'Installed',
            'plugins',
            'files',
            'directory',
            '/public/vendor',
        ];
        $this->validate($output, $expected);
    }
}
