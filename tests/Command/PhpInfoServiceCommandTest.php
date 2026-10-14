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
        $kernel = $this->mockService([
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
                    'name' => 'Invalid Client',
                    'url' => 'https://www.fake.swiss',
                ],
            ],
        ]);
        $output = $this->executeFailure(kernel: $kernel);
        self::assertOutputContainsString(
            $output,
            'URL validation for PHP v1.0',
            'Module(s) with an empty URL:',
            '- Empty Module',
            'Module(s) with an invalid URL:',
            '- Invalid URL: invalid',
            '- Invalid Client: https://www.fake.swiss',
            'Modules: 3',
            'Empty: 1',
            'Valid: 0',
            'Invalid: 2'
        );
    }

    public function testSuccess(): void
    {
        $kernel = $this->mockService([
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
                    'url' => 'https://www.php.net/manual/en/book.ftp.php',
                ],
            ],
        ]);
        $output = $this->execute(kernel: $kernel);
        self::assertOutputContainsString(
            $output,
            'URL validation for PHP v1.0',
            'Module(s) with an empty URL:',
            '- General',
            'Modules: 3',
            'Empty: 1',
            'Valid: 2',
            'Invalid: 0'
        );
    }

    #[\Override]
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    private function mockService(array $values): KernelInterface
    {
        $kernel = self::bootKernel();
        $service = self::createStub(PhpInfoService::class);
        $service->method('getPhpInfo')->willReturn($values);
        self::getContainer()->set(PhpInfoService::class, $service);

        return $kernel;
    }
}
