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
use App\Tests\DatabaseTrait;
use App\Tests\DateAssertTrait;
use App\Tests\KernelServiceTestCase;
use App\Utils\StringUtils;
use Doctrine\ORM\Exception\ORMException;

class ApplicationServiceTest extends KernelServiceTestCase
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
    }

    public function testDefaultProduct(): void
    {
        $service = $this->getApplicationService();
        self::assertNull($service->getDefaultProduct());
        self::assertSame(0.0, $service->getDefaultQuantity());
        self::assertTrue($service->isDefaultEdit());
    }

    public function testDefaultState(): void
    {
        $service = $this->getApplicationService();
        self::assertNull($service->getDefaultState());
    }

    public function testDisplayMode(): void
    {
        $service = $this->getApplicationService();
        self::assertSame(TableView::TABLE, $service->getDisplayMode());
    }

    public function testLastArchiveCalculations(): void
    {
        $service = $this->getApplicationService();
        self::assertNull($service->getLastArchiveCalculations());

        $expected = new \DateTime();
        $service->setLastArchiveCalculations($expected);

        $actual = $service->getLastArchiveCalculations();
        // @phpstan-ignore staticMethod.impossibleType
        self::assertNotNull($actual);
        self::assertSameDate($expected, $actual);
    }

    public function testLastUpdateCalculations(): void
    {
        $service = $this->getApplicationService();
        self::assertNull($service->getLastUpdateCalculations());
        $expected = new \DateTime();
        $service->setLastUpdateCalculations($expected);
        $actual = $service->getLastUpdateCalculations();
        // @phpstan-ignore staticMethod.impossibleType
        self::assertNotNull($actual);
        self::assertSameDate($expected, $actual);
    }

    public function testLastUpdateProducts(): void
    {
        $service = $this->getApplicationService();
        self::assertNull($service->getLastUpdateProducts());
        $expected = new \DateTime();
        $service->setLastUpdateProducts($expected);
        $actual = $service->getLastUpdateProducts();
        // @phpstan-ignore staticMethod.impossibleType
        self::assertNotNull($actual);
        self::assertSameDate($expected, $actual);
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
        self::assertSame(12, $service->getPanelCalculation());
    }

    public function testPasswordConstraint(): void
    {
        $service = $this->getApplicationService();
        $actual = $service->getPasswordConstraint();
        foreach (PropertyServiceInterface::PASSWORD_OPTIONS as $option) {
            $property = StringUtils::unicode($option)->trimPrefix('security_')->toString();
            self::assertFalse($actual->{$property});
        }
    }

    public function testRemoveProperty(): void
    {
        $service = $this->getApplicationService();
        self::assertFalse($service->isQrCode());
        $actual = $service->setProperty(PropertyServiceInterface::P_QR_CODE, true);
        self::assertTrue($actual);
        self::assertTrue($service->isQrCode());

        $service->removeProperty(PropertyServiceInterface::P_QR_CODE);
        self::assertFalse($service->isQrCode());
    }

    /**
     * @throws ORMException
     */
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

    /**
     * @throws ORMException
     */
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

    /**
     * @throws ORMException
     */
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

    /**
     * @throws ORMException
     */
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

    private function getApplicationService(): ApplicationService
    {
        return $this->getService(ApplicationService::class);
    }
}
