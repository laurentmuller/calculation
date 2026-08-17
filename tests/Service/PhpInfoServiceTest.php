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

use App\Service\PhpInfoService;
use PHPUnit\Framework\TestCase;

final class PhpInfoServiceTest extends TestCase
{
    public function testPhpInfo(): void
    {
        $service = new PhpInfoService();
        $actual = $service->getPhpInfo();
        self::assertSame(\PHP_VERSION, $actual['version']);
        self::assertNotEmpty($actual['modules']);
    }
}
