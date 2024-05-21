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

use App\Controller\ProductController;
use App\Tests\EntityTrait\ProductTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(ProductController::class)]
class ProductControllerTest extends AbstractControllerTestCase
{
    use ProductTrait;

    public static function getRoutes(): \Iterator
    {
        yield ['/product', self::ROLE_USER];
        yield ['/product', self::ROLE_ADMIN];
        yield ['/product', self::ROLE_SUPER_ADMIN];
        yield ['/product/add', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/product/add', self::ROLE_ADMIN];
        yield ['/product/add', self::ROLE_SUPER_ADMIN];
        yield ['/product/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/product/edit/1', self::ROLE_ADMIN];
        yield ['/product/edit/1', self::ROLE_SUPER_ADMIN];
        yield ['/product/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/product/delete/1', self::ROLE_ADMIN];
        yield ['/product/delete/1', self::ROLE_SUPER_ADMIN];
        yield ['/product/show/1', self::ROLE_USER];
        yield ['/product/show/1', self::ROLE_ADMIN];
        yield ['/product/show/1', self::ROLE_SUPER_ADMIN];
        yield ['/product/clone/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/product/clone/1', self::ROLE_ADMIN];
        yield ['/product/clone/1', self::ROLE_SUPER_ADMIN];
        yield ['/product/pdf', self::ROLE_USER];
        yield ['/product/pdf', self::ROLE_ADMIN];
        yield ['/product/pdf', self::ROLE_SUPER_ADMIN];
        yield ['/product/excel', self::ROLE_USER];
        yield ['/product/excel', self::ROLE_ADMIN];
        yield ['/product/excel', self::ROLE_SUPER_ADMIN];
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function addEntities(): void
    {
        $this->getProduct();
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteEntities(): void
    {
        $this->deleteProduct();
    }
}
