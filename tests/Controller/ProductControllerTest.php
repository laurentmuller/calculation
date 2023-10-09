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
use App\Tests\EntityTrait\CategoryTrait;
use App\Tests\EntityTrait\GroupTrait;
use App\Tests\EntityTrait\ProductTrait;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(ProductController::class)]
class ProductControllerTest extends AbstractControllerTestCase
{
    use CategoryTrait;
    use GroupTrait;
    use ProductTrait;

    public static function getRoutes(): array
    {
        return [
            ['/product', self::ROLE_USER],
            ['/product', self::ROLE_ADMIN],
            ['/product', self::ROLE_SUPER_ADMIN],

            ['/product/add', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/product/add', self::ROLE_ADMIN],
            ['/product/add', self::ROLE_SUPER_ADMIN],

            ['/product/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/product/edit/1', self::ROLE_ADMIN],
            ['/product/edit/1', self::ROLE_SUPER_ADMIN],

            ['/product/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/product/delete/1', self::ROLE_ADMIN],
            ['/product/delete/1', self::ROLE_SUPER_ADMIN],

            ['/product/show/1', self::ROLE_USER],
            ['/product/show/1', self::ROLE_ADMIN],
            ['/product/show/1', self::ROLE_SUPER_ADMIN],

            ['/product/clone/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/product/clone/1', self::ROLE_ADMIN],
            ['/product/clone/1', self::ROLE_SUPER_ADMIN],

            ['/product/pdf', self::ROLE_USER],
            ['/product/pdf', self::ROLE_ADMIN],
            ['/product/pdf', self::ROLE_SUPER_ADMIN],

            ['/product/excel', self::ROLE_USER],
            ['/product/excel', self::ROLE_ADMIN],
            ['/product/excel', self::ROLE_SUPER_ADMIN],
        ];
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function addEntities(): void
    {
        $group = $this->getGroup();
        $category = $this->getCategory($group);
        $this->getProduct($category);
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteEntities(): void
    {
        $this->deleteProduct();
        $this->deleteCategory();
        $this->deleteGroup();
    }
}
