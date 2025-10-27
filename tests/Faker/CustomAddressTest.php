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

namespace App\Tests\Faker;

use App\Faker\CustomAddress;
use App\Faker\Factory;
use App\Utils\FormatUtils;
use PHPUnit\Framework\TestCase;

final class CustomAddressTest extends TestCase
{
    public function testCustomAddress(): void
    {
        $generator = Factory::create(FormatUtils::DEFAULT_LOCALE);
        $customAddress = new CustomAddress($generator);
        $actual = $customAddress->zipCity();
        self::assertNotEmpty($actual);
    }
}
