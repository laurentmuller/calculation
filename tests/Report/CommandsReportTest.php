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

namespace App\Tests\Report;

use App\Controller\AbstractController;
use App\Report\CommandsReport;
use App\Service\CommandService;
use PHPUnit\Framework\TestCase;

/**
 * @phpstan-import-type CommandType from CommandService
 */
final class CommandsReportTest extends TestCase
{
    public function testRender(): void
    {
        $controller = $this->createMock(AbstractController::class);

        $argument1 = [
            'name' => 'Argument',
            'shortcut' => '',
            'shortcutName' => '',
            'description' => 'Description',
            'isRequired' => false,
            'isArray' => false,
            'isAcceptValue' => false,
            'default' => 'Default',
            'display' => 'Display',
            'arguments' => 'Arguments',
        ];
        $argument2 = [
            'name' => 'Argument',
            'shortcut' => '',
            'shortcutName' => '',
            'description' => '',
            'isRequired' => false,
            'isArray' => false,
            'isAcceptValue' => false,
            'default' => 'Default',
            'display' => '',
            'arguments' => '',
        ];
        $option = [
            'name' => 'Option',
            'shortcut' => 'Shortcut',
            'shortcutName' => 'Name Shortcut',
            'description' => 'Description',
            'isRequired' => true,
            'isArray' => true,
            'isAcceptValue' => true,
            'default' => 'Default',
            'display' => 'Display',
            'arguments' => 'Arguments',
        ];
        $command1 = [
            'name' => 'Command1',
            'description' => 'Description',
            'usage' => ['Usage 1', 'Usage 2'],
            'help' => '<a href="https://symfony.com>storing-uuids-in-databases">database</a>. The <span class="info">list</span>.',
            'hidden' => false,
            'arguments' => [
                'Argument1' => $argument1,
                'Argument2' => $argument2,
            ],
            'options' => [
                'Option' => $option,
            ],
        ];
        $command2 = [
            'name' => 'Command2',
            'description' => '',
            'usage' => [],
            'help' => '',
            'hidden' => false,
            'arguments' => [],
            'options' => [],
        ];
        $commands = [$command1, $command2];
        $report = new CommandsReport($controller, ['Group' => $commands]);
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
