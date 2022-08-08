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

use App\Entity\Calculation;
use App\Enums\EntityAction;
use App\Enums\MessagePosition;
use App\Enums\StrengthLevel;
use App\Enums\TableView;
use App\Service\ApplicationService;
use App\Tests\DatabaseTrait;
use App\Tests\ServiceTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Unit test for the {@link ApplicationService} class.
 */
class ApplicationServiceTest extends KernelTestCase
{
    use DatabaseTrait;
    use ServiceTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        self::bootKernel();
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testActions(): void
    {
        $service = $this->getApplicationService();
        self::assertEquals(EntityAction::EDIT, $service->getEditAction());
        self::assertTrue($service->isActionEdit());
        self::assertFalse($service->isActionShow());
        self::assertFalse($service->isActionNone());
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testAdminRole(): void
    {
        $service = $this->getApplicationService();
        $role = $service->getAdminRole();
        $rights = $service->getAdminRights();
        self::assertSame('ROLE_ADMIN', $role->getName());
        self::assertEquals($role->getRights(), $rights);
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testCustomer(): void
    {
        $service = $this->getApplicationService();
        $service->setProperties([
            'customer_name' => 'customer_name',
            'customer_url' => 'customer_url',
        ]);

        self::assertEquals('customer_name', $service->getCustomerName());
        self::assertEquals('customer_url', $service->getCustomerUrl());

        $customer = $service->getCustomer();
        self::assertEquals('customer_name', $customer->getName());
        self::assertEquals('customer_url', $customer->getUrl());
        self::assertNull($customer->getAddress());
        self::assertNull($customer->getEmail());
        self::assertNull($customer->getFax());
        self::assertNull($customer->getPhone());
        self::assertFalse($customer->isPrintAddress());
        self::assertNull($customer->getZipCity());
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testDates(): void
    {
        $service = $this->getApplicationService();
        self::assertNull($service->getLastImport());
        self::assertNull($service->getUpdateProducts());
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testDefaultCategory(): void
    {
        $service = $this->getApplicationService();
        self::assertNull($service->getDefaultCategory());
        self::assertEquals(0, $service->getDefaultCategoryId());
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testDefaultProduct(): void
    {
        $service = $this->getApplicationService();
        self::assertNull($service->getDefaultProduct());
        self::assertEquals(0, $service->getDefaultProductId());
        self::assertEquals(0, $service->getDefaultQuantity());
        self::assertTrue($service->isDefaultEdit());
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testDefaultState(): void
    {
        $service = $this->getApplicationService();
        self::assertNull($service->getDefaultState());
        self::assertEquals(0, $service->getDefaultStateId());
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testDisplayMode(): void
    {
        $service = $this->getApplicationService();
        self::assertEquals(TableView::TABLE, $service->getDisplayMode());
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testMessage(): void
    {
        $service = $this->getApplicationService();
        self::assertEquals(MessagePosition::BOTTOM_RIGHT, $service->getMessagePosition());
        self::assertEquals(4000, $service->getMessageTimeout());
        self::assertFalse($service->isMessageSubTitle());
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testMinMargin(): void
    {
        $service = $this->getApplicationService();
        self::assertEquals(1.1, $service->getMinMargin());
        self::assertTrue($service->isMarginBelow(1.0));
        self::assertFalse($service->isMarginBelow(1.2));

        $calculation = new Calculation();
        self::assertFalse($service->isMarginBelow($calculation));
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testOptions(): void
    {
        $service = $this->getApplicationService();
        self::assertFalse($service->isQrCode());
        self::assertFalse($service->isPrintAddress());
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testPanels(): void
    {
        $service = $this->getApplicationService();
        self::assertTrue($service->isPanelCatalog());
        self::assertTrue($service->isPanelMonth());
        self::assertTrue($service->isPanelState());
        self::assertEquals(10, $service->getPanelCalculation());
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testSecurity(): void
    {
        $service = $this->getApplicationService();
        self::assertFalse($service->isDisplayCaptcha());
        self::assertEquals(StrengthLevel::NONE, $service->getStrengthLevel());
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testUserRole(): void
    {
        $service = $this->getApplicationService();
        $role = $service->getUserRole();
        $rights = $service->getUserRights();
        self::assertSame('ROLE_USER', $role->getName());
        self::assertEquals($role->getRights(), $rights);
    }

    private function getApplicationService(): ApplicationService
    {
        return $this->getService(ApplicationService::class);
    }
}
