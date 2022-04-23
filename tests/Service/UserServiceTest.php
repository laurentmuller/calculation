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
use App\Enums\TableView;
use App\Service\UserService;
use App\Tests\DatabaseTrait;
use App\Tests\ServiceTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 *  Unit test for the {@link UserService} class.
 */
class UserServiceTest extends KernelTestCase
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

    public function testActions(): void
    {
        $service = $this->getUserService();
        $this->assertEquals(EntityAction::EDIT, $service->getEditAction());
        $this->assertTrue($service->isActionEdit());
        $this->assertFalse($service->isActionShow());
        $this->assertFalse($service->isActionNone());
    }

    public function testDisplayMode(): void
    {
        $service = $this->getUserService();
        $this->assertEquals(TableView::TABLE, $service->getDisplayMode());
    }

    public function testMessage(): void
    {
        $service = $this->getUserService();
        $this->assertEquals('bottom-right', $service->getMessagePosition());
        $this->assertEquals(4000, $service->getMessageTimeout());
        $this->assertFalse($service->isMessageSubTitle());
    }

    public function testOptions(): void
    {
        $service = $this->getUserService();
        $this->assertFalse($service->isQrCode());
        $this->assertFalse($service->isPrintAddress());
    }

    public function testPanels(): void
    {
        $service = $this->getUserService();
        $this->assertTrue($service->isPanelCatalog());
        $this->assertTrue($service->isPanelMonth());
        $this->assertTrue($service->isPanelState());
        $this->assertEquals(10, $service->getPanelCalculation());
    }

    private function getUserService(): UserService
    {
        return $this->getService(UserService::class);
    }
}
