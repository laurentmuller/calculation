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
use Symfony\Component\Clock\DatePoint;

final class CalculationTest extends EntityValidatorTestCase
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

        $calculation->updateCodes();
        $group->setCode('group1');
        $category->setCode('category1');
        $calculation->updateCodes();
        self::assertSame(0.0, $calculation->getOverallMarginAmount());

        self::assertSame(1.1, $calculation->getGroupsMargin());
        self::assertSame(0.3, \round($calculation->getGroupsMarginAmount(), 2));
        self::assertSame(3.3, \round($calculation->getGroupsTotal(), 2));
        self::assertSame(1, $calculation->getCategoriesCount());

        self::assertTrue($calculation->isSortable());
        $calculation->sort();
    }

    public function testCategory(): void
    {
        $entity = new CalculationCategory();
        self::assertNull($entity->getCode());
        self::assertNull($entity->getGroup());
        self::assertNull($entity->getParentEntity());
        self::assertTrue($entity->isEmpty());
        self::assertFalse($entity->isSortable());

        $expected = 'code';
        $entity->setCode($expected);

        $actualCode = $entity->getCode();
        self::assertSame($expected, $actualCode); // @phpstan-ignore-line

        $actualDisplay = $entity->getDisplay();
        self::assertSame($expected, $actualDisplay); // @phpstan-ignore-line

        $category = new Category();
        $category->setCode('code');
        $entity->setCategory($category, true);
        self::assertNotNull($entity->getCategory());

        self::assertCount(0, $entity);
        $item = new CalculationItem();
        $entity->removeItem($item);
        self::assertCount(0, $entity);
    }

    /**
     * @throws \ReflectionException
     */
    public function testClone(): void
    {
        $calculation = new Calculation();

        $item = new CalculationItem();
        self::setId($item, 10);

        $category = new CalculationCategory();
        self::setId($category, 20);

        $group = new CalculationGroup();
        self::setId($group, 30);

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

    public function testCompare(): void
    {
        $item1 = new CalculationGroup();
        $item1->setCode('Code1');
        self::assertFalse($item1->isSortable());
        $item1->sort();
        $item2 = new CalculationGroup();
        $item2->setCode('Code2');
        $actual = $item1->compare($item2);
        self::assertSame(-1, $actual);

        $item1 = new CalculationCategory();
        $item1->setCode('Code1');
        self::assertFalse($item1->isSortable());
        $item1->sort();
        $item2 = new CalculationCategory();
        $item2->setCode('Code2');
        $actual = $item1->compare($item2);
        self::assertSame(-1, $actual);

        $item1 = new CalculationItem();
        $item1->setDescription('Description1');
        $item2 = new CalculationItem();
        $item2->setDescription('Description2');
        $actual = $item1->compare($item2);
        self::assertSame(-1, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testDisplay(): void
    {
        $calculation = new Calculation();
        self::assertSame('000000', $calculation->getDisplay());
        self::assertSame($calculation->getFormattedId(), $calculation->getDisplay());

        self::setId($calculation, 112233);
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
        self::assertEmpty($calculation->getDuplicateItems());

        $calculation->addProduct($product);
        self::assertFalse($calculation->hasDuplicateItems());
        self::assertEmpty($calculation->getDuplicateItems());

        $calculation->addProduct($product);
        self::assertTrue($calculation->hasDuplicateItems());
        self::assertCount(2, $calculation->getDuplicateItems());

        $calculation->removeDuplicateItems();
        self::assertFalse($calculation->hasDuplicateItems());
        self::assertEmpty($calculation->getDuplicateItems());
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
        self::assertEmpty($calculation->getEmptyItems());

        $calculation->addProduct($product);
        self::assertTrue($calculation->hasEmptyItems());
        self::assertCount(1, $calculation->getEmptyItems());

        $calculation->addProduct($product, 0.0);
        self::assertTrue($calculation->hasEmptyItems());
        self::assertCount(2, $calculation->getEmptyItems());

        $calculation->removeEmptyItems();
        self::assertFalse($calculation->hasEmptyItems());
        self::assertEmpty($calculation->getEmptyItems());
    }

    public function testEmptySortedGroup(): void
    {
        $calculation = new Calculation();
        $actual = $calculation->getSortedGroups();
        self::assertSame([], $actual);
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

        $date = new DatePoint();
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
        $date = new DatePoint('2000-01-01');
        $calculation = new Calculation();
        $calculation->setDate($date);
        $actual = $calculation->getFormattedDate();
        $expected = FormatUtils::formatDate($date);
        self::assertSame($expected, $actual);
    }

    public function testGroup(): void
    {
        $entity = new CalculationGroup();
        self::assertNull($entity->getCode());
        self::assertNull($entity->getGroup());
        self::assertNull($entity->getParentEntity());
        self::assertSame(0.0, $entity->getMargin());
        self::assertSame(0.0, $entity->getAmount());

        $expected = 'code';
        $entity->setCode($expected);

        $actualCode = $entity->getCode();
        self::assertSame($expected, $actualCode); // @phpstan-ignore-line

        $actualDisplay = $entity->getDisplay();
        self::assertSame($entity->getCode(), $actualDisplay);

        $group = new Group();
        $group->setCode('code');
        $entity->setGroup($group, true);
        self::assertNotNull($entity->getGroup());

        $category = new CalculationCategory();
        self::assertCount(0, $entity);
        $entity->removeCategory($category);
        self::assertCount(0, $entity);

        $calculation = new Calculation();
        self::assertEmpty($calculation->getGroups());
        self::assertSame(0, $calculation->getGroupsCount());
        self::assertSame(0, $calculation->getCategoriesCount());
        self::assertFalse($calculation->contains($entity));

        $calculation->addGroup($entity);
        self::assertCount(1, $calculation->getGroups());
        self::assertSame(1, $calculation->getGroupsCount());
        self::assertTrue($calculation->contains($entity));

        $calculation->removeGroup($entity);
        self::assertEmpty($calculation->getGroups());
        self::assertSame(0, $calculation->getGroupsCount());
        self::assertFalse($calculation->contains($entity));
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

    public function testItem(): void
    {
        $entity = new CalculationItem();
        self::assertTrue($entity->isEmpty());
        self::assertTrue($entity->isEmptyPrice());
        self::assertTrue($entity->isEmptyQuantity());
        self::assertNull($entity->getCategory());
        self::assertNull($entity->getParentEntity());

        $expected = 1.0;
        $entity->setPrice($expected);
        self::assertSame($expected, $entity->getPrice());
        self::assertFalse($entity->isEmptyPrice());

        $entity->setQuantity($expected);
        self::assertSame($expected, $entity->getQuantity());
        self::assertFalse($entity->isEmptyQuantity());

        $expected = 'description';
        $entity->setDescription($expected);
        self::assertSame($expected, $entity->getDescription());
        self::assertSame($expected, $entity->getDisplay());

        $expected = 'unit';
        self::assertNull($entity->getUnit());
        $entity->setUnit($expected);
        self::assertSame($expected, $entity->getUnit());
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

    public function testRemoveEmptyItems(): void
    {
        $calculation = new Calculation();
        $group = new CalculationGroup();
        $category = new CalculationCategory();
        $item = new CalculationItem();

        $category->addItem($item);
        $group->addCategory($category);
        $calculation->addGroup($group);
        self::assertCount(1, $calculation->getGroups());

        $calculation->removeEmptyItems();
        self::assertEmpty($calculation->getGroups());
    }

    public function testRemoveEmptyItemsWithoutCategory(): void
    {
        $calculation = new Calculation();
        $group = new CalculationGroup();
        $category = new CalculationCategory();
        $item = new CalculationItem();

        $category->addItem($item);
        $group->addCategory($category);
        $calculation->addGroup($group);
        self::assertCount(1, $calculation->getGroups());

        $item->setCategory(null);
        $calculation->removeEmptyItems();
        self::assertCount(1, $calculation->getGroups());
    }

    public function testRemoveEmptyItemsWithoutGroup(): void
    {
        $calculation = new Calculation();
        $group = new CalculationGroup();
        $category = new CalculationCategory();
        $item = new CalculationItem();

        $category->addItem($item);
        $group->addCategory($category);
        $calculation->addGroup($group);
        self::assertCount(1, $calculation->getGroups());

        $category->setGroup(null);
        $calculation->removeEmptyItems();
        self::assertCount(1, $calculation->getGroups());
    }

    public function testRemoveGroup(): void
    {
        $calculation = new Calculation();
        self::assertEmpty($calculation->getGroups());
        $group = new CalculationGroup();
        $calculation->removeGroup($group);
        self::assertEmpty($calculation->getGroups());
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

    public function testSortCategories(): void
    {
        $group = new CalculationGroup();
        $category1 = new CalculationCategory();
        $category1->setCode('category1');

        $item1 = new CalculationItem();
        $item1->setDescription('description1');
        $category1->addItem($item1);

        $item2 = new CalculationItem();
        $item2->setDescription('description2');
        $category1->addItem($item2);

        $category2 = new CalculationCategory();
        $category2->setCode('category2');

        $group->addCategory($category1);
        $group->addCategory($category2);

        $category1->setPosition(-1);
        $category2->setPosition(-1);

        $actual = $group->sort();
        self::assertTrue($actual);
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
