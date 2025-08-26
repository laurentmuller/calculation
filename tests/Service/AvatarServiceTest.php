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

use App\Service\AvatarService;
use PHPUnit\Framework\TestCase;

class AvatarServiceTest extends TestCase
{
    public function testGetURL(): void
    {
        $service = new AvatarService();

        $actual = $service->getURL(name: 'user');
        self::assertSame('https://robohash.org/user?size=32x32', $actual);

        $actual = $service->getURL(name: 'user', size: 0);
        self::assertSame('https://robohash.org/user', $actual);

        $actual = $service->getURL(name: 'user', size: 16);
        self::assertSame('https://robohash.org/user?size=16x16', $actual);

        $actual = $service->getURL(name: 'user', set: 2);
        self::assertSame('https://robohash.org/user?size=32x32&set=set2', $actual);

        $actual = $service->getURL(name: 'user', background: 2);
        self::assertSame('https://robohash.org/user?size=32x32&bgset=bg2', $actual);

        $actual = $service->getURL(name: 'user', size: 24, set: 2, background: 2);
        self::assertSame('https://robohash.org/user?size=24x24&set=set2&bgset=bg2', $actual);
    }
}
