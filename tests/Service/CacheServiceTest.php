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

namespace App\Tests\Service;

use App\Model\CommandResult;
use App\Service\CacheService;
use App\Service\CommandService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Console\Command\Command;

class CacheServiceTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testClear(): void
    {
        $result = new CommandResult(Command::SUCCESS, '');
        $service = $this->createService($result);
        $actual = $service->clear();
        self::assertTrue($actual);
    }

    public function testEmptyList(): void
    {
        $result = new CommandResult(Command::FAILURE, '');
        $service = $this->createService($result);
        $actual = $service->list();
        self::assertEmpty($actual);
    }

    public function testNotEmptyList(): void
    {
        $content = <<<CONTENT
                 ------------------------------------------------------------------ 
                  Pool name                                                         
                 ------------------------------------------------------------------ 
                  cache.doctrine.orm.default.result                                 
                  cache.doctrine.orm.default.query   
            CONTENT;

        $result = new CommandResult(Command::SUCCESS, $content);
        $service = $this->createService($result);

        $actual = $service->list();
        self::assertNotEmpty($actual);
        self::assertArrayHasKey('cache', $actual);

        $actual = $actual['cache'];
        self::assertCount(2, $actual);
        self::assertContains('doctrine.orm.default.result', $actual);
        self::assertContains('doctrine.orm.default.query', $actual);
    }

    private function createService(CommandResult $result): CacheService
    {
        $service = $this->createMock(CommandService::class);
        $service->expects(self::once())
            ->method('execute')
            ->willReturn($result);

        return new CacheService($service, new ArrayAdapter());
    }
}
