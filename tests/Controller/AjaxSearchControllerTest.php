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

use App\Controller\AjaxSearchController;
use App\Tests\EntityTrait\ProductTrait;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AjaxSearchController::class)]
class AjaxSearchControllerTest extends ControllerTestCase
{
    use ProductTrait;

    /**
     * @throws ORMException
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->getProduct();
    }

    /**
     * @throws ORMException
     */
    protected function tearDown(): void
    {
        $this->deleteProduct();
        parent::tearDown();
    }

    public static function getRoutes(): \Iterator
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
