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

use App\Service\LogFinderService;
use PHPUnit\Framework\TestCase;

final class LogFinderServiceTest extends TestCase
{
    public function testFindFound(): void
    {
        $service = $this->createService(__DIR__ . '/../files/log');
        $files = $service->find();
        self::assertCount(4, $files);
    }

    public function testFindNotFound(): void
    {
        $service = $this->createService(__DIR__);
        $files = $service->find();
        self::assertCount(0, $files);
    }

    private function createService(string $path): LogFinderService
    {
        return new LogFinderService($path);
    }
}
