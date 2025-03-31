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

namespace App\Tests\Controller;

use App\Tests\EntityTrait\ProductTrait;

class AjaxSearchControllerTest extends ControllerTestCase
{
    use ProductTrait;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->getProduct();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->deleteProduct();
        parent::tearDown();
    }

    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/ajax/search/address', self::ROLE_USER];
        yield ['/ajax/search/address?zip=1000', self::ROLE_USER];
        yield ['/ajax/search/address?city=paris', self::ROLE_USER];
        yield ['/ajax/search/address?street=route', self::ROLE_USER];

        yield ['/ajax/search/customer', self::ROLE_USER];
        yield ['/ajax/search/customer?query=john', self::ROLE_USER];

        yield ['/ajax/search/product', self::ROLE_USER];
        yield ['/ajax/search/product?query=description', self::ROLE_USER];

        yield ['/ajax/search/supplier', self::ROLE_USER];
        yield ['/ajax/search/supplier?query=value', self::ROLE_USER];

        yield ['/ajax/search/title', self::ROLE_USER];
        yield ['/ajax/search/title?query=value', self::ROLE_USER];

        yield ['/ajax/search/unit', self::ROLE_USER];
        yield ['/ajax/search/unit?query=value', self::ROLE_USER];
    }
}
