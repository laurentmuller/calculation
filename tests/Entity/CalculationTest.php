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

namespace App\Tests\Entity;

use App\Entity\Calculation;
use App\Entity\CalculationCategory;
use App\Entity\CalculationGroup;
use App\Entity\CalculationItem;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Group;
use App\Entity\GroupMargin;
use App\Entity\Product;
use App\Utils\FormatUtils;

#[\PHPUnit\Framework\Attributes\CoversClass(Calculation::class)]
class CalculationTest extends AbstractEntityValidatorTestCase
{
    use IdTrait;

    public function testAddProduct(): void
    {
        $product1 = new Product();
        $product1->setDescription('product2')
            ->setPrice(1.0);
        $product2 = new Product();
        $product2->setDescription('product1')
            ->setPrice(2.0);

        $category = new Category();
        $category->setCode('category')
            ->addProduct($product1)
            ->addProduct($product2);
        $margin = new GroupMargin();
        $margin->setMinimum(0.0)
            ->setMaximum(1000.0)
            ->setMargin(1.1);
        $group = new Group();
        $group->setCode('group')
            ->addCategory($category)
            ->addMargin($margin);

        $calculation = new Calculation();
        $calculation->addProduct($product1);
        self::assertSame(1, $calculation->getLinesCount());
        $calculation->addProduct($product2);
        self::assertSame(2, $calculation->getLinesCount());

        $calculation->setGlobalMargin(1.0);
        foreach ($calculation->getGroups() as $currentGroup) {
            $currentGroup->update();
        }
        $calculation->updatePositions();
        $calculation->getSortedGroups();

        $group->setCode('group1');
        $category->setCode('category1');
        $calculation->updateCodes();
        $calculation->getOverallMarginAmount();

        self::assertSame(1.1, $calculation->getGroupsMargin());
        self::assertSame(0.3, \round($calculation->getGroupsMarginAmount(), 2));
        self::assertSame(3.3, \round($calculation->getGroupsTotal(), 2));
        self::assertSame(1, $calculation->getCategoriesCount());

        self::assertTrue($calculation->isSortable());
        $calculation->sort();
    }

    /**
     * @throws \ReflectionException
     */
    public function testClone(): void
    {
        $calculation = new Calculation();

        $item = new CalculationItem();
        $this->setId($item, 10);

        $category = new CalculationCategory();
        $this->setId($category, 20);

        $group = new CalculationGroup();
        $this->setId($group, 30);

        $category->addItem($item);
        $group->addCategory($category);
        $calculation->addGroup($group);

        $clone = clone $calculation;
        foreach ($clone->getGroups() as $currentGroup) {
            self::assertNull($currentGroup->getId());
            foreach ($currentGroup->getCategories() as $currentCategory) {
                self::assertNull($currentCategory->getId());
                foreach ($currentCategory->getItems() as $currentItem) {
                    self::assertNull($currentItem->getId());
                }
            }
        }

        foreach ($clone->getItems() as $currentItem) {
            self::assertNull($currentItem->getId());
        }

        $state = new CalculationState();
        $calculation = new Calculation();
        $calculation->setState($state);
        $calculation->setDescription('description');

        $clone = $calculation->clone();
        self::assertSame($calculation->getState(), $clone->getState());
        self::assertSame($calculation->getDescription(), $clone->getDescription());

        $newState = new CalculationState();
        $clone = $calculation->clone($newState);
        self::assertSame($newState, $clone->getState());

        $clone = $calculation->clone(description: 'new-description');
        self::assertSame('new-description', $clone->getDescription());
    }

    /**
     * @throws \ReflectionException
     */
    public function testDisplay(): void
    {
        $calculation = new Calculation();
        self::assertSame('000000', $calculation->getDisplay());
        self::assertSame($calculation->getFormattedId(), $calculation->getDisplay());

        $this->setId($calculation, 112233);
        self::assertSame('112233', $calculation->getDisplay());
        self::assertSame($calculation->getFormattedId(), $calculation->getDisplay());
    }

    public function testDuplicateItems(): void
    {
        $product = new Product();
        $product->setDescription('product')
            ->setPrice(1.0);

        $category = new Category();
        $category->setCode('category')
            ->addProduct($product);
        $margin = new GroupMargin();
        $margin->setMinimum(0.0)
            ->setMaximum(1000.0)
            ->setMargin(1.1);
        $group = new Group();
        $group->setCode('group')
            ->addCategory($category)
            ->addMargin($margin);

        $calculation = new Calculation();
        self::assertFalse($calculation->hasDuplicateItems());
        self::assertCount(0, $calculation->getDuplicateItems());
        $calculation->addProduct($product);
        self::assertFalse($calculation->hasDuplicateItems());
        self::assertCount(0, $calculation->getDuplicateItems());
        $calculation->addProduct($product);
        self::assertTrue($calculation->hasDuplicateItems());
        self::assertCount(2, $calculation->getDuplicateItems());

        $calculation->removeDuplicateItems();
        self::assertFalse($calculation->hasDuplicateItems());
    }

    public function testEmptyItems(): void
    {
        $product = new Product();
        $product->setDescription('product')
            ->setPrice(0.0);

        $category = new Category();
        $category->setCode('category')
            ->addProduct($product);
        $margin = new GroupMargin();
        $margin->setMinimum(0.0)
            ->setMaximum(1000.0)
            ->setMargin(1.1);
        $group = new Group();
        $group->setCode('group')
            ->addCategory($category)
            ->addMargin($margin);

        $calculation = new Calculation();
        self::assertFalse($calculation->hasEmptyItems());
        self::assertCount(0, $calculation->getEmptyItems());
        $calculation->addProduct($product);
        self::assertTrue($calculation->hasEmptyItems());
        self::assertCount(1, $calculation->getEmptyItems());
        $calculation->addProduct($product, 0.0);
        self::assertTrue($calculation->hasEmptyItems());
        self::assertCount(2, $calculation->getEmptyItems());

        $calculation->removeEmptyItems();
        self::assertFalse($calculation->hasEmptyItems());
    }

    public function testFields(): void
    {
        $calculation = new Calculation();
        self::assertSame(1.0, $calculation->getGroupsMargin());
        self::assertSame(0.0, $calculation->getGroupsMarginAmount());
        self::assertSame(0.0, $calculation->getGroupsTotal());

        self::assertSame(0.0, $calculation->getItemsTotal());
        self::assertSame(0, $calculation->getLinesCount());

        self::assertSame(0.0, $calculation->getOverallMargin());
        self::assertSame(0.0, $calculation->getOverallMarginAmount());
        self::assertSame(0.0, $calculation->getOverallTotal());

        self::assertSame(0.0, $calculation->getTotalNet());

        self::assertSame(0.0, $calculation->getUserMargin());
        self::assertSame(0.0, $calculation->getUserMarginAmount());
        self::assertSame(0.0, $calculation->getUserMarginTotal());

        self::assertSame(1.0, $calculation->getGroupsMargin());
        self::assertSame(0.0, $calculation->getGroupsMarginAmount());

        self::assertSame(0.0, $calculation->getGlobalMargin());
        self::assertSame(0.0, $calculation->getGlobalMarginAmount());

        self::assertFalse($calculation->hasDuplicateItems());
        self::assertFalse($calculation->hasEmptyItems());
        self::assertFalse($calculation->isSortable());

        self::assertTrue($calculation->isEditable());
        self::assertTrue($calculation->isEmpty());

        $date = new \DateTime();
        $calculation->setDate($date);
        self::assertSame($date, $calculation->getDate());

        $description = 'description';
        self::assertNull($calculation->getDescription());
        $calculation->setDescription($description);
        self::assertSame($description, $calculation->getDescription());

        $customer = 'customer';
        $calculation->setCustomer($customer);
        self::assertSame($customer, $calculation->getCustomer());

        $calculation->removeEmptyItems();
        self::assertFalse($calculation->hasEmptyItems());
        $calculation->removeDuplicateItems();
        self::assertFalse($calculation->hasDuplicateItems());

        $calculation->setUserMargin(100);
        self::assertSame(100.0, $calculation->getUserMargin());

        $calculation->setItemsTotal(100.0);
        self::assertSame(100.0, $calculation->getItemsTotal());

        $calculation->setOverallTotal(200.0);
        self::assertSame(200.0, $calculation->getOverallTotal());
    }

    public function testFormattedDate(): void
    {
        $date = new \DateTimeImmutable('2000-01-01');
        $calculation = new Calculation();
        $calculation->setDate($date);
        $actual = $calculation->getFormattedDate();
        $expected = FormatUtils::formatDate($date);
        self::assertSame($expected, $actual);
    }

    public function testGroup(): void
    {
        $group = new CalculationGroup();
        $calculation = new Calculation();
        self::assertCount(0, $calculation->getGroups());
        self::assertSame(0, $calculation->getGroupsCount());
        self::assertSame(0, $calculation->getCategoriesCount());
        self::assertFalse($calculation->contains($group));

        $calculation->addGroup($group);
        self::assertCount(1, $calculation->getGroups());
        self::assertSame(1, $calculation->getGroupsCount());
        self::assertTrue($calculation->contains($group));

        $calculation->removeGroup($group);
        self::assertCount(0, $calculation->getGroups());
        self::assertSame(0, $calculation->getGroupsCount());
        self::assertFalse($calculation->contains($group));
    }

    public function testInvalidAll(): void
    {
        $calculation = new Calculation();
        $results = $this->validate($calculation, 3);
        $this->validatePaths($results, 'customer', 'description', 'state');
    }

    public function testInvalidCustomer(): void
    {
        $calculation = new Calculation();
        $calculation->setDescription('my description')
            ->setState($this->getState());
        $this->validate($calculation, 1);
    }

    public function testInvalidDescription(): void
    {
        $calculation = new Calculation();
        $calculation->setCustomer('my customer')
            ->setState($this->getState());
        $results = $this->validate($calculation, 1);
        $this->validatePaths($results, 'description');
    }

    public function testInvalidState(): void
    {
        $calculation = new Calculation();
        $calculation->setDescription('my description')
            ->setCustomer('my customer');
        $results = $this->validate($calculation, 1);
        $this->validatePaths($results, 'state');
    }

    public function testMarginBelow(): void
    {
        $calculation = new Calculation();
        self::assertFalse($calculation->isMarginBelow(0.0));

        $product = new Product();
        $product->setDescription('product1')
            ->setPrice(1.0);

        $category = new Category();
        $category->setCode('category')
            ->addProduct($product);

        $margin = new GroupMargin();
        $margin->setMinimum(0.0)
            ->setMaximum(1000.0)
            ->setMargin(1.1);
        $group = new Group();
        $group->setCode('group')
            ->addCategory($category);

        $calculation->addProduct($product);
        $calculation->setItemsTotal(100);
        $calculation->setOverallTotal(130);
        self::assertTrue($calculation->isMarginBelow(3.0));
    }

    public function testOverallMarginAmount(): void
    {
        $calculation = new Calculation();
        $calculation->setItemsTotal(100.0);
        $calculation->setOverallTotal(200.0);
        self::assertSame(100.0, $calculation->getOverallMarginAmount());
    }

    public function testRemoveGroup(): void
    {
        $calculation = new Calculation();
        self::assertCount(0, $calculation->getGroups());
        $group = new CalculationGroup();
        $calculation->removeGroup($group);
        self::assertCount(0, $calculation->getGroups());
    }

    public function testSortable(): void
    {
        $group = new CalculationGroup();
        $group->setCode('group');
        $calculation = new Calculation();
        $calculation->addGroup($group);
        self::assertFalse($calculation->isSortable());
        $calculation->sort();
    }

    public function testState(): void
    {
        $state = new CalculationState();
        $state->setCode('code')
            ->setColor('color');

        $calculation = new Calculation();
        self::assertNull($calculation->getState());
        self::assertNull($calculation->getStateCode());
        self::assertNull($calculation->getStateColor());

        $calculation->setState($state);
        self::assertNotNull($calculation->getState());
        self::assertSame('code', $calculation->getStateCode());
        self::assertSame('color', $calculation->getStateColor());
    }

    public function testValid(): void
    {
        $calculation = new Calculation();
        $calculation->setDescription('my description')
            ->setCustomer('my customer')
            ->setState($this->getState());
        $this->validate($calculation);
    }

    private function getState(): CalculationState
    {
        $state = new CalculationState();
        $state->setCode('my code');

        return $state;
    }
}
