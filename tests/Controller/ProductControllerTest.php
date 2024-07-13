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

use App\Entity\Product;
use App\Interfaces\PropertyServiceInterface;
use App\Service\ApplicationService;
use App\Tests\EntityTrait\ProductTrait;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Component\HttpFoundation\Response;

class ProductControllerTest extends EntityControllerTestCase
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
     * @throws ORMException
     */
    public function testAdd(): void
    {
        $category = $this->getCategory();
        $service = $this->getService(ApplicationService::class);
        $service->setProperties([
            PropertyServiceInterface::P_DEFAULT_CATEGORY => $category,
        ]);
        $data = [
            'product[description]' => 'Description',
            'product[category]' => $category->getId(),
            'product[price]' => 1.0,
            'product[unit]' => 'm2',
            'product[supplier]' => 'Supplier',
        ];
        $this->checkAddEntity('/product/add', $data);
    }

    /**
     * @throws ORMException
     */
    public function testDelete(): void
    {
        $this->addEntities();
        $uri = \sprintf('/product/delete/%d', (int) $this->getProduct()->getId());
        $this->checkDeleteEntity($uri);
    }

    /**
     * @throws ORMException
     */
    public function testEdit(): void
    {
        $this->addEntities();
        $uri = \sprintf('/product/edit/%d', (int) $this->getProduct()->getId());
        $data = [
            'product[description]' => 'New Description',
            'product[category]' => $this->getCategory()->getId(),
            'product[price]' => 2.0,
            'product[unit]' => 'km',
            'product[supplier]' => 'New Supplier',
        ];
        $this->checkEditEntity($uri, $data);
    }

    /**
     * @throws ORMException
     */
    public function testExcelEmpty(): void
    {
        $this->checkUriWithEmptyEntity('/product/excel', Product::class);
    }

    /**
     * @throws ORMException
     */
    public function testPdfEmpty(): void
    {
        $this->checkUriWithEmptyEntity('/product/pdf', Product::class);
    }

    /**
     * @throws ORMException
     */
    protected function addEntities(): void
    {
        $this->getProduct();
    }

    /**
     * @throws ORMException
     */
    protected function deleteEntities(): void
    {
        $this->deleteProduct();
    }
}
