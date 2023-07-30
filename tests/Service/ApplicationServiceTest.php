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

#[\PHPUnit\Framework\Attributes\CoversClass(ApplicationService::class)]
class ApplicationServiceTest extends KernelTestCase
{
    use DatabaseTrait;
    use ServiceTrait;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testActions(): void
    {
        $service = $this->getApplicationService();
        self::assertSame(EntityAction::EDIT, $service->getEditAction());
        self::assertTrue($service->isActionEdit());
        self::assertFalse($service->isActionShow());
        self::assertFalse($service->isActionNone());
    }

    public function testAdminRole(): void
    {
        $service = $this->getApplicationService();
        $role = $service->getAdminRole();
        $rights = $service->getAdminRights();
        self::assertSame('ROLE_ADMIN', $role->getName());
        self::assertSame($role->getRights(), $rights);
    }

    public function testCustomer(): void
    {
        $service = $this->getApplicationService();
        $service->setProperties([
            'customer_name' => 'customer_name',
            'customer_url' => 'customer_url',
        ]);

        self::assertSame('customer_name', $service->getCustomerName());
        self::assertSame('customer_url', $service->getCustomerUrl());

        $customer = $service->getCustomer();
        self::assertSame('customer_name', $customer->getName());
        self::assertSame('customer_url', $customer->getUrl());
        self::assertNull($customer->getAddress());
        self::assertNull($customer->getEmail());
        self::assertNull($customer->getFax());
        self::assertNull($customer->getPhone());
        self::assertFalse($customer->isPrintAddress());
        self::assertNull($customer->getZipCity());
    }

    public function testDates(): void
    {
        $service = $this->getApplicationService();
        self::assertNull($service->getLastImport());
        self::assertNull($service->getLastUpdateProducts());
    }

    public function testDefaultCategory(): void
    {
        $service = $this->getApplicationService();
        self::assertNull($service->getDefaultCategory());
        self::assertSame(0, $service->getDefaultCategoryId());
    }

    public function testDefaultProduct(): void
    {
        $service = $this->getApplicationService();
        self::assertNull($service->getDefaultProduct());
        self::assertSame(0, $service->getDefaultProductId());
        self::assertSame(0.0, $service->getDefaultQuantity());
        self::assertTrue($service->isDefaultEdit());
    }

    public function testDefaultState(): void
    {
        $service = $this->getApplicationService();
        self::assertNull($service->getDefaultState());
        self::assertSame(0, $service->getDefaultStateId());
    }

    public function testDisplayMode(): void
    {
        $service = $this->getApplicationService();
        self::assertSame(TableView::TABLE, $service->getDisplayMode());
    }

    public function testMessage(): void
    {
        $service = $this->getApplicationService();
        self::assertSame(MessagePosition::BOTTOM_RIGHT, $service->getMessagePosition());
        self::assertSame(4000, $service->getMessageTimeout());
        self::assertFalse($service->isMessageSubTitle());
    }

    public function testMinMargin(): void
    {
        $service = $this->getApplicationService();
        self::assertSame(1.1, $service->getMinMargin());
        self::assertTrue($service->isMarginBelow(1.0));
        self::assertFalse($service->isMarginBelow(1.2));

        $calculation = new Calculation();
        self::assertFalse($service->isMarginBelow($calculation));
    }

    public function testOptions(): void
    {
        $service = $this->getApplicationService();
        self::assertFalse($service->isQrCode());
        self::assertFalse($service->isPrintAddress());
    }

    public function testPanels(): void
    {
        $service = $this->getApplicationService();
        self::assertTrue($service->isPanelCatalog());
        self::assertTrue($service->isPanelMonth());
        self::assertTrue($service->isPanelState());
        self::assertSame(10, $service->getPanelCalculation());
    }

    public function testSecurity(): void
    {
        $service = $this->getApplicationService();
        if ($service->isDebug()) {
            self::assertFalse($service->isDisplayCaptcha());
        } else {
            self::assertTrue($service->isDisplayCaptcha());
        }
        self::assertSame(StrengthLevel::NONE, $service->getStrengthLevel());
    }

    public function testUserRole(): void
    {
        $service = $this->getApplicationService();
        $role = $service->getUserRole();
        $rights = $service->getUserRights();
        self::assertSame('ROLE_USER', $role->getName());
        self::assertSame($role->getRights(), $rights);
    }

    private function getApplicationService(): ApplicationService
    {
        return $this->getService(ApplicationService::class);
    }
}
