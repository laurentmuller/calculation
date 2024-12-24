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

namespace App\Tests\Model;

use App\Model\CustomerInformation;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\TestCase;

class CustomerInformationTest extends TestCase
{
    use TranslatorMockTrait;

    public function testDefaultValues(): void
    {
        $info = new CustomerInformation();
        self::assertNull($info->getAddress());
        self::assertNull($info->getEmail());
        self::assertNull($info->getName());
        self::assertNull($info->getPhone());
        self::assertNull($info->getUrl());
        self::assertNull($info->getZipCity());
        self::assertFalse($info->isPrintAddress());
    }

    public function testSetValues(): void
    {
        $info = new CustomerInformation();
        $info->setAddress('address');
        $info->setEmail('email');
        $info->setName('name');
        $info->setPhone('phone');
        $info->setPrintAddress(true);
        $info->setUrl('url');
        $info->setZipCity('zipCity');

        self::assertSame('address', $info->getAddress());
        self::assertSame('email', $info->getEmail());
        self::assertSame('name', $info->getName());
        self::assertSame('phone', $info->getPhone());
        self::assertSame('url', $info->getUrl());
        self::assertSame('zipCity', $info->getZipCity());
        self::assertTrue($info->isPrintAddress());
    }

    public function trans(string $id): string
    {
        return $id;
    }
}
