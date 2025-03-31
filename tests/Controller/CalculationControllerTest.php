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

use App\Entity\Calculation;
use App\Interfaces\PropertyServiceInterface;
use App\Service\ApplicationService;
use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\EntityTrait\ProductTrait;

class CalculationControllerTest extends EntityControllerTestCase
{
    use CalculationTrait;
    use ProductTrait;

    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/calculation', self::ROLE_USER];
        yield ['/calculation', self::ROLE_ADMIN];
        yield ['/calculation', self::ROLE_SUPER_ADMIN];

        yield ['/calculation/add', self::ROLE_USER];
        yield ['/calculation/add', self::ROLE_ADMIN];
        yield ['/calculation/add', self::ROLE_SUPER_ADMIN];

        yield ['/calculation/edit/1', self::ROLE_USER];
        yield ['/calculation/edit/1', self::ROLE_ADMIN];
        yield ['/calculation/edit/1', self::ROLE_SUPER_ADMIN];

        yield ['/calculation/state/1', self::ROLE_USER];
        yield ['/calculation/state/1', self::ROLE_ADMIN];
        yield ['/calculation/state/1', self::ROLE_SUPER_ADMIN];

        yield ['/calculation/delete/1', self::ROLE_USER];
        yield ['/calculation/delete/1', self::ROLE_ADMIN];
        yield ['/calculation/delete/1', self::ROLE_SUPER_ADMIN];

        yield ['/calculation/clone/1', self::ROLE_USER];
        yield ['/calculation/clone/1', self::ROLE_ADMIN];
        yield ['/calculation/clone/1', self::ROLE_SUPER_ADMIN];

        yield ['/calculation/show/1', self::ROLE_USER];
        yield ['/calculation/show/1', self::ROLE_ADMIN];
        yield ['/calculation/show/1', self::ROLE_SUPER_ADMIN];

        yield ['/calculation/pdf/1', self::ROLE_USER];
        yield ['/calculation/pdf/1', self::ROLE_ADMIN];
        yield ['/calculation/pdf/1', self::ROLE_SUPER_ADMIN];

        yield ['/calculation/pdf', self::ROLE_USER];
        yield ['/calculation/pdf', self::ROLE_ADMIN];
        yield ['/calculation/pdf', self::ROLE_SUPER_ADMIN];

        yield ['/calculation/excel', self::ROLE_USER];
        yield ['/calculation/excel', self::ROLE_ADMIN];
        yield ['/calculation/excel', self::ROLE_SUPER_ADMIN];

        yield ['/calculation/excel/1', self::ROLE_USER];
        yield ['/calculation/excel/1', self::ROLE_ADMIN];
        yield ['/calculation/excel/1', self::ROLE_SUPER_ADMIN];

        yield ['/calculation?search=22', self::ROLE_USER];
        yield ['/calculation?search=22', self::ROLE_ADMIN];
        yield ['/calculation?search=22', self::ROLE_SUPER_ADMIN];
    }

    public function testAdd(): void
    {
        $state = $this->getCalculationState();
        $service = $this->getService(ApplicationService::class);
        $service->setProperties([
            PropertyServiceInterface::P_DEFAULT_STATE => $state,
            PropertyServiceInterface::P_PRODUCT_DEFAULT => $this->getProduct(),
        ]);

        $data = [
            'calculation[customer]' => 'Customer',
            'calculation[description]' => 'Description',
        ];
        $this->checkAddEntity('/calculation/add', $data);
    }

    public function testEditState(): void
    {
        $this->addEntities();
        $uri = \sprintf('/calculation/state/%d', (int) $this->getCalculation()->getId());
        $data = [
            'calculation_edit_state[state]' => $this->getCalculationState()->getId(),
        ];
        $this->checkEditEntity($uri, $data);
    }

    public function testExcelEmpty(): void
    {
        $this->checkUriWithEmptyEntity('/calculation/excel', Calculation::class);
    }

    public function testPdfEmpty(): void
    {
        $this->checkUriWithEmptyEntity('/calculation/pdf', Calculation::class);
    }

    public function testWithQrCode(): void
    {
        $service = $this->getService(ApplicationService::class);
        $service->setProperties([
            PropertyServiceInterface::P_QR_CODE => true,
        ]);

        $this->addEntities();

        try {
            $calculation = $this->getCalculation();
            $uri = \sprintf('/calculation/pdf/%d', (int) $calculation->getId());
            $this->checkRoute($uri, self::ROLE_USER);
        } finally {
            $service->setProperties([
                PropertyServiceInterface::P_QR_CODE => false,
            ]);
        }
    }

    #[\Override]
    protected function addEntities(): void
    {
        $product = $this->getProduct();
        $this->getCalculation()
            ->setOverallTotal(100.0)
            ->setItemsTotal(100.0)
            ->setGlobalMargin(1.1)
            ->setUserMargin(0.1)
            ->addProduct($product);
        $this->addEntity($this->getCalculation());
    }

    #[\Override]
    protected function deleteEntities(): void
    {
        $this->deleteCalculation();
        $this->deleteCategory();
    }
}
