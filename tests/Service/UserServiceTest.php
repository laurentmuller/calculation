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

use App\Entity\User;
use App\Enums\EntityAction;
use App\Enums\MessagePosition;
use App\Enums\TableView;
use App\Interfaces\PropertyServiceInterface;
use App\Repository\UserPropertyRepository;
use App\Repository\UserRepository;
use App\Service\ApplicationService;
use App\Service\UserService;
use App\Tests\DatabaseTrait;
use App\Tests\KernelServiceTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class UserServiceTest extends KernelServiceTestCase
{
    use DatabaseTrait;

    public function testActions(): void
    {
        $service = $this->createUserService();
        self::assertSame(EntityAction::EDIT, $service->getEditAction());
        self::assertTrue($service->isActionEdit());
        self::assertFalse($service->isActionShow());
        self::assertFalse($service->isActionNone());
    }

    public function testDarkNavigation(): void
    {
        $service = $this->createUserService();
        $actual = $service->isDarkNavigation();
        self::assertTrue($actual);
    }

    public function testDisplayMode(): void
    {
        $service = $this->createUserService();
        self::assertSame(TableView::TABLE, $service->getDisplayMode());
    }

    public function testGetApplication(): void
    {
        $service = $this->createUserService();
        $application = $service->getApplication();
        self::assertFalse($application->isPrintAddress());
    }

    public function testGetCustomer(): void
    {
        $service = $this->createUserService();
        $customer = $service->getCustomer();
        $actual = $customer->getAddress();
        self::assertNull($actual);
    }

    public function testGetMessageAttributes(): void
    {
        $service = $this->createUserService();
        $actual = $service->getMessageAttributes();
        self::assertCount(7, $actual);
    }

    public function testGetProperties(): void
    {
        $service = $this->createUserService();
        $actual = $service->getProperties();
        self::assertNotEmpty($actual);
    }

    public function testMessage(): void
    {
        $service = $this->createUserService();
        self::assertSame(MessagePosition::BOTTOM_RIGHT, $service->getMessagePosition());
        self::assertSame(4000, $service->getMessageTimeout());
        self::assertFalse($service->isMessageSubTitle());
    }

    public function testOptions(): void
    {
        $service = $this->createUserService();
        self::assertFalse($service->isQrCode());
        self::assertFalse($service->isPrintAddress());
    }

    public function testPanels(): void
    {
        $service = $this->createUserService();
        self::assertTrue($service->isPanelCatalog());
        self::assertTrue($service->isPanelMonth());
        self::assertTrue($service->isPanelState());
        self::assertSame(12, $service->getCalculations());
    }

    public function testSetPropertiesAndRemove(): void
    {
        $user = $this->getUser(1);
        $service = $this->createUserService($user);

        try {
            $service->setProperties(['qr_code' => true]);
            $actual = $service->setProperties(['qr_code' => false]);
            self::assertTrue($actual);
        } finally {
            $service->setProperties(['qr_code' => null]);
        }
    }

    public function testSetPropertiesEmpty(): void
    {
        $service = $this->createUserService();
        $actual = $service->setProperties([]);
        self::assertFalse($actual);
    }

    public function testSetPropertiesNoUser(): void
    {
        $service = $this->createUserService();

        try {
            $actual = $service->setProperties([
                'fake' => 'value',
            ]);

            self::assertFalse($actual);
        } finally {
            $service->setProperties(['fake' => null]);
        }
    }

    public function testSetPropertiesSame(): void
    {
        $service = $this->createUserService();
        $darkNavigation = $service->isDarkNavigation();
        $actual = $service->setProperty(PropertyServiceInterface::P_DARK_NAVIGATION, $darkNavigation);
        self::assertFalse($actual);
    }

    public function testSetPropertiesWithUser(): void
    {
        $user = $this->getUser(1);
        $service = $this->createUserService($user);

        try {
            $actual = $service->setProperties(['fake' => 'value']);

            self::assertTrue($actual);
        } finally {
            $service->setProperties(['fake' => null]);
        }
    }

    private function createUserService(?User $user = null): UserService
    {
        $repository = $this->getService(UserPropertyRepository::class);
        $application = $this->getService(ApplicationService::class);
        $security = $this->createMock(Security::class);
        $security->method('getUser')
            ->willReturn($user);
        $cacheItemPool = new ArrayAdapter();

        return new UserService($repository, $application, $security, $cacheItemPool);
    }

    private function getUser(int $id): ?User
    {
        return $this->getService(UserRepository::class)
            ->find($id);
    }
}
