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
        $this->assertStringContainsString('Check versions', $output);
    }

    public function testExecuteUpdate(): void
    {
        $output = $this->execute();
        $this->assertStringContainsString('[OK]', $output);
        $this->assertStringContainsString('/public/js/vendor', $output);
    }

    private function execute(array $options = []): string
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:update-assets');
        $tester = new CommandTester($command);
        $tester->execute($options);

        $tester->assertCommandIsSuccessful();

        return $tester->getDisplay();
    }
}
