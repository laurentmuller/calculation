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
use App\Service\UserService;
use App\Tests\DatabaseTrait;
use App\Tests\ServiceTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Unit test for the {@link UserService} class.
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
        self::assertSame(EntityAction::EDIT, $service->getEditAction());
        self::assertTrue($service->isActionEdit());
        self::assertFalse($service->isActionShow());
        self::assertFalse($service->isActionNone());
    }

    public function testDisplayMode(): void
    {
        $service = $this->getUserService();
        self::assertSame(TableView::TABLE, $service->getDisplayMode());
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
        self::assertSame(10, $service->getPanelCalculation());
    }

    private function getUserService(): UserService
    {
        return $this->getService(UserService::class);
    }
}
