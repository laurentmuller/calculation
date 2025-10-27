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
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\GlobalProperty;
use App\Entity\Group;
use App\Entity\Product;
use App\Enums\EntityAction;
use App\Enums\MessagePosition;
use App\Enums\StrengthLevel;
use App\Enums\TableView;
use App\Interfaces\PropertyServiceInterface;
use App\Service\ApplicationService;
use App\Service\RoleBuilderService;
use App\Tests\DatabaseTrait;
use App\Tests\DateAssertTrait;
use App\Tests\KernelServiceTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\Clock\DatePoint;

final class ApplicationServiceTest extends KernelServiceTestCase
{
    use DatabaseTrait;
    use DateAssertTrait;

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

    public function testClearFail(): void
    {
        $cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $cacheItemPool->method('clear')
            ->willReturn(false);
        $service = $this->createApplicationService($cacheItemPool);
        $actual = $service->clearCache();
        self::assertFalse($actual);
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
    }

    public function testDefaultProduct(): void
    {
        $service = $this->getApplicationService();
        self::assertNull($service->getDefaultProduct());
        self::assertSame(0.0, $service->getDefaultQuantity());
        self::assertFalse($service->isDefaultEdit());
    }

    public function testDefaultState(): void
    {
        $service = $this->getApplicationService();
        self::assertNull($service->getDefaultState());
    }

    public function testDeleteCacheItemFail(): void
    {
        self::expectException(\LogicException::class);
        $cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $cacheItemPool->method('deleteItem')
            ->willThrowException(new InvalidArgumentException());
        $service = $this->createApplicationService($cacheItemPool);
        $service->deleteCacheItem('fake');
    }

    public function testDisplayMode(): void
    {
        $service = $this->getApplicationService();
        self::assertSame(TableView::TABLE, $service->getDisplayMode());
    }

    public function testGetCacheItemFail(): void
    {
        self::expectException(\LogicException::class);
        $cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $cacheItemPool->method('getItem')
            ->willThrowException(new InvalidArgumentException());
        $service = $this->createApplicationService($cacheItemPool);

        $service->getCacheItem('fake');
    }

    public function testInvalidJson(): void
    {
        $key = PropertyServiceInterface::P_ADMIN_RIGHTS;
        $service = $this->getApplicationService();
        $service->setProperty($key, '{');
        $actual = $service->getAdminRights();
        self::assertNotEmpty($actual);
    }

    public function testLastArchiveCalculations(): void
    {
        $service = $this->getApplicationService();
        self::assertNull($service->getLastArchiveCalculations());

        $expected = new DatePoint();
        $service->setLastArchiveCalculations($expected);

        $actual = $service->getLastArchiveCalculations();
        // @phpstan-ignore staticMethod.impossibleType
        self::assertNotNull($actual);
        self::assertTimestampEquals($expected, $actual);
    }

    public function testLastUpdateCalculations(): void
    {
        $service = $this->getApplicationService();
        self::assertNull($service->getLastUpdateCalculations());
        $expected = new DatePoint();
        $service->setLastUpdateCalculations($expected);
        $actual = $service->getLastUpdateCalculations();
        // @phpstan-ignore staticMethod.impossibleType
        self::assertNotNull($actual);
        self::assertTimestampEquals($expected, $actual);
    }

    public function testLastUpdateProducts(): void
    {
        $service = $this->getApplicationService();
        self::assertNull($service->getLastUpdateProducts());
        $expected = new DatePoint();
        $service->setLastUpdateProducts($expected);
        $actual = $service->getLastUpdateProducts();
        // @phpstan-ignore staticMethod.impossibleType
        self::assertNotNull($actual);
        self::assertTimestampEquals($expected, $actual);
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
        self::assertSame(12, $service->getCalculations());
    }

    public function testPasswordConstraint(): void
    {
        $service = $this->getApplicationService();
        $password = $service->getPasswordConstraint();
        foreach (PropertyServiceInterface::PASSWORD_OPTIONS as $option) {
            self::assertFalse($password->isOption($option));
        }
    }

    public function testRemoveProperty(): void
    {
        $service = $this->getApplicationService();
        self::assertFalse($service->isQrCode());

        $actual = $service->setProperty(PropertyServiceInterface::P_QR_CODE, true);
        self::assertTrue($actual);
        self::assertTrue($service->isQrCode());

        $actual = $service->removeProperty(PropertyServiceInterface::P_QR_CODE);
        self::assertTrue($actual);
        self::assertFalse($service->isQrCode());
    }

    public function testSaveDeferredCacheValueFail(): void
    {
        $item = $this->createMock(CacheItemInterface::class);
        $cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $cacheItemPool->method('getItem')
            ->willReturn($item);
        $cacheItemPool->method('saveDeferred')
            ->willReturn(false);
        $service = $this->createApplicationService($cacheItemPool);
        $actual = $service->saveDeferredCacheValue('fake', 'fake');
        self::assertFalse($actual);
    }

    public function testSaveExistingProperty(): void
    {
        $manager = $this->getManager();
        $property = new GlobalProperty();
        $property->setName(PropertyServiceInterface::P_QR_CODE)
            ->setValue(false);
        $manager->persist($property);
        $manager->flush();

        $service = $this->getApplicationService();
        $actual = $service->setProperty(PropertyServiceInterface::P_QR_CODE, true);
        self::assertTrue($actual);
        $actual = $service->setProperty(PropertyServiceInterface::P_QR_CODE, false);
        self::assertTrue($actual);
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

    public function testSetPropertiesEmpty(): void
    {
        $service = $this->getApplicationService();
        $actual = $service->setProperties([]);
        self::assertFalse($actual);
    }

    public function testSetPropertiesSame(): void
    {
        $service = $this->getApplicationService();
        $margin = $service->getMinMargin();
        $actual = $service->setProperty(PropertyServiceInterface::P_MIN_MARGIN, $margin);
        self::assertFalse($actual);
    }

    public function testStrengthConstraint(): void
    {
        $value = StrengthLevel::WEAK;
        $service = $this->getApplicationService();
        $service->setProperty(PropertyServiceInterface::P_STRENGTH_LEVEL, $value);
        $actual = $service->getStrengthConstraint();
        self::assertSame($value, $actual->minimum);
    }

    public function testUpdateDeletedCategory(): void
    {
        $service = $this->getApplicationService();
        self::assertNull($service->getDefaultCategory());

        $group = new Group();
        $group->setCode('group');
        $category = new Category();
        $category->setCode('category')
            ->setGroup($group);
        $manager = $this->getManager();
        $manager->persist($group);
        $manager->persist($category);
        $manager->flush();

        $actual = $service->setProperty(PropertyServiceInterface::P_DEFAULT_CATEGORY, $category);
        self::assertTrue($actual);

        $actual = $service->getDefaultCategory();
        // @phpstan-ignore staticMethod.impossibleType
        self::assertNotNull($actual);
        self::assertSame($category->getId(), $actual->getId());

        $service->updateDeletedCategory($category);
        self::assertNull($service->getDefaultCategory());

        $manager->remove($category);
        $manager->remove($group);
        $manager->flush();
    }

    public function testUpdateDeletedProduct(): void
    {
        $service = $this->getApplicationService();
        self::assertNull($service->getDefaultProduct());

        $group = new Group();
        $group->setCode('group');
        $category = new Category();
        $category->setCode('category')
            ->setGroup($group);
        $product = new Product();
        $product->setDescription('product')
            ->setCategory($category);

        $manager = $this->getManager();
        $manager->persist($group);
        $manager->persist($category);
        $manager->persist($product);
        $manager->flush();

        $actual = $service->setProperty(PropertyServiceInterface::P_PRODUCT_DEFAULT, $product);
        self::assertTrue($actual);

        $actual = $service->getDefaultProduct();
        // @phpstan-ignore staticMethod.impossibleType
        self::assertNotNull($actual);
        self::assertSame($product->getId(), $actual->getId());

        $service->updateDeletedProduct($product);
        self::assertNull($service->getDefaultProduct());

        $manager->remove($product);
        $manager->remove($category);
        $manager->remove($group);
        $manager->flush();
    }

    public function testUpdateDeletedState(): void
    {
        $service = $this->getApplicationService();
        self::assertNull($service->getDefaultState());

        $state = new CalculationState();
        $state->setCode('code');
        $manager = $this->getManager();
        $manager->persist($state);
        $manager->flush();

        $actual = $service->setProperty(PropertyServiceInterface::P_DEFAULT_STATE, $state);
        self::assertTrue($actual);

        $actual = $service->getDefaultState();
        // @phpstan-ignore staticMethod.impossibleType
        self::assertNotNull($actual);
        self::assertSame($state->getId(), $actual->getId());

        $service->updateDeletedState($state);
        self::assertNull($service->getDefaultState());

        $manager->remove($state);
        $manager->flush();
    }

    public function testUserRole(): void
    {
        $service = $this->getApplicationService();
        $role = $service->getUserRole();
        $rights = $service->getUserRights();
        self::assertSame('ROLE_USER', $role->getName());
        self::assertSame($role->getRights(), $rights);
    }

    public function testValidJson(): void
    {
        $key = PropertyServiceInterface::P_ADMIN_RIGHTS;
        $service = $this->getApplicationService();
        $rights = $service->getAdminRights();
        $service->setProperty($key, $rights);
        $actual = $service->getAdminRights();
        self::assertNotEmpty($actual);
    }

    private function createApplicationService(CacheItemPoolInterface $cacheItemPool): ApplicationService
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $builder = $this->createMock(RoleBuilderService::class);

        return new ApplicationService($manager, $builder, false, $cacheItemPool);
    }

    private function getApplicationService(): ApplicationService
    {
        return $this->getService(ApplicationService::class);
    }
}
