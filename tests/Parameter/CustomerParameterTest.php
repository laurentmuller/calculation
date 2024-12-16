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

namespace App\Tests\Parameter;

use App\Parameter\CustomerParameter;

/**
 * @extends AbstractParameterTestCase<CustomerParameter>
 */
class CustomerParameterTest extends AbstractParameterTestCase
{
    public static function getParameterNames(): \Generator
    {
        yield ['address', 'customer_address'];
        yield ['email', 'customer_email'];
        yield ['fax', 'customer_fax'];
        yield ['name', 'customer_name'];
        yield ['phone', 'customer_phone'];
        yield ['url', 'customer_url'];
        yield ['zipCity', 'customer_zip_city'];
    }

    public static function getParameterValues(): \Generator
    {
        yield ['address', null];
        yield ['email', null];
        yield ['fax', null];
        yield ['name', null];
        yield ['phone', null];
        yield ['url', null];
        yield ['zipCity', null];
    }

    public function testDefaultValue(): void
    {
        self::assertNull($this->parameter->getAddress());
        self::assertNull($this->parameter->getEmail());
        self::assertNull($this->parameter->getFax());
        self::assertNull($this->parameter->getName());
        self::assertNull($this->parameter->getPhone());
        self::assertNull($this->parameter->getUrl());
        self::assertNull($this->parameter->getZipCity());

        self::assertSame('parameter_customer', $this->parameter::getCacheKey());
    }

    public function testSetValue(): void
    {
        $this->parameter->setAddress('address');
        self::assertSame('address', $this->parameter->getAddress());
        $this->parameter->setEmail('email');
        self::assertSame('email', $this->parameter->getEmail());
        $this->parameter->setFax('fax');
        self::assertSame('fax', $this->parameter->getFax());
        $this->parameter->setName('name');
        self::assertSame('name', $this->parameter->getName());
        $this->parameter->setPhone('phone');
        self::assertSame('phone', $this->parameter->getPhone());
        $this->parameter->setUrl('url');
        self::assertSame('url', $this->parameter->getUrl());
        $this->parameter->setZipCity('zipCity');
        self::assertSame('zipCity', $this->parameter->getZipCity());
    }

    protected function createParameter(): CustomerParameter
    {
        return new CustomerParameter();
    }
}
