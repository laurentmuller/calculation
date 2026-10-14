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

use App\Service\PhpInfoService;
use Symfony\Component\HttpKernel\KernelInterface;

final class PhpInfoServiceCommandTest extends CommandTestCase
{
    private const string COMMAND_NAME = 'app:phpinfo';

    public function testFailure(): void
    {
        $kernel = $this->createService([
            'version' => '1.0',
            'modules' => [
                [
                    'name' => 'Empty Module',
                    'url' => null,
                ],
                [
                    'name' => 'Invalid URL',
                    'url' => 'invalid',
                ],
                [
                    'name' => 'Invalid CURL',
                    'url' => 'https://www.not_exist_fake.fake',
                ],
            ],
        ]);
        $output = $this->executeFailure(kernel: $kernel);
        self::assertOutputContainsString(
            $output,
            '1.0',
            'Module(s) with an empty URL:',
            'Empty Module',
            'Module(s) with an invalid URL:',
            'Invalid URL',
            'invalid',
            'Invalid CURL',
            'https://www.not_exist_fake.fake'
        );
    }

    public function testSuccess(): void
    {
        $kernel = $this->createService([
            'version' => '1.0',
            'modules' => [
                [
                    'name' => 'General',
                    'url' => null,
                ],
                [
                    'name' => 'bcmath',
                    'url' => 'https://www.php.net/manual/en/book.bc.php',
                ],
                [
                    'name' => 'ftp',
                    'url' => 'https://www.php.net/manual/en/book.bc.php',
                ],
            ],
        ]);
        $output = $this->execute(kernel: $kernel);
        self::assertOutputContainsString(
            $output,
            'Modules',
            'Empty',
            'Valid',
            'Invalid'
        );
    }

    #[\Override]
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    private function createService(array $values): KernelInterface
    {
        $kernel = self::bootKernel();
        $service = self::createStub(PhpInfoService::class);
        $service->method('getPhpInfo')->willReturn($values);
        self::getContainer()->set(PhpInfoService::class, $service);

        return $kernel;
    }
}
