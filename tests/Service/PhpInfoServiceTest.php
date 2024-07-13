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

class PhpInfoServiceTest extends TestCase
{
    public function testAsArray(): void
    {
        $service = new PhpInfoService();
        $actual = $service->asArray();
        self::assertEmpty($actual);
    }

    public function testAsHtml(): void
    {
        $service = new PhpInfoService();
        $actual = $service->asHtml();
        self::assertNotEmpty($actual);
    }

    public function testAsText(): void
    {
        $service = new PhpInfoService();
        $actual = $service->asText();
        self::assertNotEmpty($actual);
    }

    public function testGetVersion(): void
    {
        $service = new PhpInfoService();
        $actual = $service->getVersion();
        self::assertSame(\PHP_VERSION, $actual);
    }
}
