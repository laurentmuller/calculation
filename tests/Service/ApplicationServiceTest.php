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

use App\Service\ApplicationService;
use PHPUnit\Framework\TestCase;

final class ApplicationServiceTest extends TestCase
{
    public function testService(): void
    {
        self::assertNotEmpty(ApplicationService::APP_DESCRIPTION);
        self::assertSame('Calculation v3.0.0', ApplicationService::APP_FULL_NAME);
        self::assertSame('Calculation', ApplicationService::APP_NAME);
        self::assertSame('3.0.0', ApplicationService::APP_VERSION);

        self::assertSame('Montévraz', ApplicationService::OWNER_CITY);
        self::assertSame('calculation@bibi.nu', ApplicationService::OWNER_EMAIL);
        self::assertSame('bibi.nu', ApplicationService::OWNER_NAME);
        self::assertSame('https://www.bibi.nu', ApplicationService::OWNER_URL);

        $actual = ApplicationService::getOwnerAddress();
        self::assertSame('calculation@bibi.nu', $actual->getAddress());
        self::assertSame('Calculation', $actual->getName());
    }
}
