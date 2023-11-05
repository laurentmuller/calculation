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
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[\PHPUnit\Framework\Attributes\CoversClass(UpdateAssetsCommand::class)]
class UpdateAssetsCommandTest extends KernelTestCase
{
    public function testExecuteDryRun(): void
    {
        $output = $this->execute(['--dry-run' => true]);
        $values = [
            'Check versions:',
            'jquery',
            'bootstrap',
            'font-awesome',
            'jquery-validate',
            'highcharts',
            'html5sortable',
            'mark.js',
            'jquery-contextmenu',
            'clipboard.js',
            'bootstrap-table',
        ];
        $this->validate($output, $values);
    }

    public function testExecuteUpdate(): void
    {
        $output = $this->execute();
        $values = [
            '[OK]',
            'Installed',
            'plugins',
            'files',
            'directory',
            '/public/vendor',
        ];
        $this->validate($output, $values);
    }

    private function execute(array $input = []): string
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:update-assets');
        $tester = new CommandTester($command);
        $tester->execute($input);

        $tester->assertCommandIsSuccessful();

        return $tester->getDisplay();
    }

    /**
     * @psalm-param string[] $values
     */
    private function validate(string $output, array $values): void
    {
        foreach ($values as $value) {
            self::assertStringContainsString($value, $output);
        }
    }
}
