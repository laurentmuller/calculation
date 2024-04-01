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

use App\Enums\EntityAction;
use App\Enums\MessagePosition;
use App\Enums\TableView;
use App\Interfaces\PropertyServiceInterface;
use App\Service\UserService;
use App\Tests\DatabaseTrait;
use App\Tests\ServiceTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(UserService::class)]
class UserServiceTest extends KernelTestCase
{
    use DatabaseTrait;
    use ServiceTrait;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testActions(): void
    {
        $service = $this->getUserService();
        self::assertSame(EntityAction::EDIT, $service->getEditAction());
        self::assertTrue($service->isActionEdit());
        self::assertFalse($service->isActionShow());
        self::assertFalse($service->isActionNone());
    }

    public function testDarkNavigation(): void
    {
        $service = $this->getUserService();
        $actual = $service->isDarkNavigation();
        self::assertTrue($actual);
    }

    public function testDisplayMode(): void
    {
        $service = $this->getUserService();
        self::assertSame(TableView::TABLE, $service->getDisplayMode());
    }

    public function testGetApplication(): void
    {
        $service = $this->getUserService();
        $application = $service->getApplication();
        self::assertFalse($application->isPrintAddress());
    }

    public function testGetCustomer(): void
    {
        $service = $this->getUserService();
        $actual = $service->getCustomer();
        self::assertNotNull($actual->getName());
    }

    public function testGetMessageAttributes(): void
    {
        $service = $this->getUserService();
        $actual = $service->getMessageAttributes();
        self::assertCount(7, $actual);
    }

    public function testGetProperties(): void
    {
        $service = $this->getUserService();
        $actual = $service->getProperties();
        self::assertNotEmpty($actual);
    }

    public function testMessage(): void
    {
        $service = $this->getUserService();
        self::assertSame(MessagePosition::BOTTOM_RIGHT, $service->getMessagePosition());
        self::assertSame(4000, $service->getMessageTimeout());
        self::assertFalse($service->isMessageSubTitle());
    }

    public function testOptions(): void
    {
        $service = $this->getUserService();
        self::assertFalse($service->isQrCode());
        self::assertFalse($service->isPrintAddress());
    }

    public function testPanels(): void
    {
        $service = $this->getUserService();
        self::assertTrue($service->isPanelCatalog());
        self::assertTrue($service->isPanelMonth());
        self::assertTrue($service->isPanelState());
        self::assertSame(12, $service->getPanelCalculation());
    }

    public function testSetPropertiesEmpty(): void
    {
        $service = $this->getUserService();
        $actual = $service->setProperties([]);
        self::assertFalse($actual);
    }

    public function testSetPropertiesSame(): void
    {
        $service = $this->getUserService();
        $darkNavigation = $service->isDarkNavigation();
        $actual = $service->setProperty(PropertyServiceInterface::P_DARK_NAVIGATION, $darkNavigation);
        self::assertFalse($actual);
    }

    private function getUserService(): UserService
    {
        return $this->getService(UserService::class);
    }
}
