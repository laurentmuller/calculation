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
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(UpdateAssetsCommand::class)]
class UpdateAssetsCommandTest extends KernelTestCase
{
    public function testExecuteDryRun(): void
    {
        $output = $this->execute(['--dry-run' => true]);
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
        $output = $this->execute();
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

    private function execute(array $input = []): string
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $command = $application->find('app:update-assets');

        $tester = new CommandTester($command);
        $result = $tester->execute($input);
        self::assertSame(Command::SUCCESS, $result);

        $tester->assertCommandIsSuccessful();

        return $tester->getDisplay();
    }

    /**
     * @psalm-param string[] $expected
     */
    private function validate(string $output, array $expected): void
    {
        foreach ($expected as $value) {
            self::assertStringContainsString($value, $output);
        }
    }
}
