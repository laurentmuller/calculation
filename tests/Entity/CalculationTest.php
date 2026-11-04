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
        $product1->withDescription('product2')
            ->withPrice(1.0);
        $product2 = new Product();
        $product2->withDescription('product1')
            ->withPrice(2.0);

        $category = new Category();
        $category->withCode('category')
            ->addProduct($product1)
            ->addProduct($product2);
        $margin = new GroupMargin();
        $margin->withMinimum(0.0)
            ->withMaximum(1000.0)
            ->withMargin(1.1);
        $group = new Group();
        $group->withCode('group')
            ->addCategory($category)
            ->addMargin($margin);

        $calculation = new Calculation();
        $calculation->addProduct($product1);
        self::assertSame(1, $calculation->itemsCount);
        $calculation->addProduct($product2);
        self::assertSame(2, $calculation->itemsCount);

        $calculation->withGlobalMargin(1.0);
        foreach ($calculation->groups as $currentGroup) {
            $currentGroup->update();
        }
        $calculation->updatePositions();
        $calculation->getSortedGroups();

        $calculation->updateCodes();
        $group->withCode('group1');
        $category->withCode('category1');
        $calculation->updateCodes();
        self::assertSame(0.0, $calculation->overallMarginAmount);

        self::assertSame(1.1, $calculation->groupsMargin);
        self::assertSame(0.3, \round($calculation->groupsMarginAmount, 2));
        self::assertSame(3.3, \round($calculation->groupsTotal, 2));
        self::assertSame(1, $calculation->categoriesCount);

        self::assertTrue($calculation->isSortable());
        $calculation->sort();
    }

    public function testCalculationCategory(): void
    {
        $entity = new CalculationCategory();
        self::assertNull($entity->code);
        self::assertNull($entity->group);
        self::assertNull($entity->getParentEntity());
        self::assertTrue($entity->isEmpty());
        self::assertFalse($entity->isSortable());

        $entity->withCode('code');
        self::assertSame('code', $entity->code);
        self::assertSame('code', $entity->getDisplay());

        $category = new Category();
        $category->withCode('code');
        $entity->withCategory($category, true);
        self::assertNotNull($entity->category);

        self::assertCount(0, $entity);
        $item = new CalculationItem();
        $entity->removeItem($item);
        self::assertCount(0, $entity);
    }

    public function testCalculationGroup(): void
    {
        $entity = new CalculationGroup();
        self::assertNull($entity->code);
        self::assertNull($entity->group);
        self::assertNull($entity->getParentEntity());
        self::assertSame(0.0, $entity->margin);
        self::assertSame(0.0, $entity->amount);

        $entity->withCode('code');
        self::assertSame('code', $entity->code);
        self::assertSame('code', $entity->getDisplay());

        $group = new Group();
        $group->withCode('code');
        $entity->withGroup($group, true);
        self::assertNotNull($entity->group);

        $category = new CalculationCategory();
        self::assertCount(0, $entity);
        $entity->removeCategory($category);
        self::assertCount(0, $entity);

        $calculation = new Calculation();
        self::assertEmpty($calculation->groups);
        self::assertSame(0, $calculation->groupsCount);
        self::assertSame(0, $calculation->categoriesCount);
        self::assertFalse($calculation->contains($entity));

        $calculation->addGroup($entity);
        self::assertCount(1, $calculation->groups);
        self::assertSame(1, $calculation->groupsCount);
        self::assertTrue($calculation->contains($entity));

        $calculation->removeGroup($entity);
        self::assertEmpty($calculation->groups);
        self::assertSame(0, $calculation->groupsCount);
        self::assertFalse($calculation->contains($entity));
    }

    public function testCalculationItem(): void
    {
        $entity = new CalculationItem();
        self::assertTrue($entity->isEmpty());
        self::assertTrue($entity->isEmptyPrice());
        self::assertTrue($entity->isEmptyQuantity());
        self::assertNull($entity->category);
        self::assertNull($entity->getParentEntity());

        $expected = 1.0;
        $entity->withPrice($expected);
        self::assertSame($expected, $entity->price);
        self::assertFalse($entity->isEmptyPrice());

        $entity->withQuantity($expected);
        self::assertSame($expected, $entity->quantity);
        self::assertFalse($entity->isEmptyQuantity());

        $expected = 'description';
        $entity->withDescription($expected);
        self::assertSame($expected, $entity->description);
        self::assertSame($expected, $entity->getDisplay());

        $expected = 'unit';
        self::assertNull($entity->unit);
        $entity->withUnit($expected);
        self::assertSame($expected, $entity->unit);
    }

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
        foreach ($clone->groups as $currentGroup) {
            self::assertNull($currentGroup->id);
            foreach ($currentGroup->categories as $currentCategory) {
                self::assertNull($currentCategory->id);
                foreach ($currentCategory->items as $currentItem) {
                    self::assertNull($currentItem->id);
                }
            }
        }

        foreach ($clone->getItems() as $currentItem) {
            self::assertNull($currentItem->id);
        }

        $state = new CalculationState();
        $calculation = new Calculation();
        $calculation->withState($state);
        $calculation->withDescription('description');

        $clone = $calculation->clone();
        self::assertSame($calculation->state, $clone->state);
        self::assertSame($calculation->description, $clone->description);

        $newState = new CalculationState();
        $clone = $calculation->clone($newState);
        self::assertSame($newState, $clone->state);

        $clone = $calculation->clone(description: 'new-description');
        self::assertSame('new-description', $clone->description);
    }

    public function testCompare(): void
    {
        $item1 = new CalculationGroup();
        $item1->withCode('Code1');
        self::assertFalse($item1->isSortable());
        $item1->sort();
        $item2 = new CalculationGroup();
        $item2->withCode('Code2');
        $actual = $item1->compare($item2);
        self::assertSame(-1, $actual);

        $item1 = new CalculationCategory();
        $item1->withCode('Code1');
        self::assertFalse($item1->isSortable());
        $item1->sort();
        $item2 = new CalculationCategory();
        $item2->withCode('Code2');
        $actual = $item1->compare($item2);
        self::assertSame(-1, $actual);

        $item1 = new CalculationItem();
        $item1->withDescription('Description1');
        $item2 = new CalculationItem();
        $item2->withDescription('Description2');
        $actual = $item1->compare($item2);
        self::assertSame(-1, $actual);
    }

    public function testDisplay(): void
    {
        $calculation = new Calculation();
        self::assertSame('000000', $calculation->getDisplay());
        self::assertSame($calculation->formattedId, $calculation->getDisplay());

        self::setId($calculation, 112233);
        self::assertSame('112233', $calculation->getDisplay());
        self::assertSame($calculation->formattedId, $calculation->getDisplay());
    }

    public function testDuplicateItems(): void
    {
        $product = new Product();
        $product->withDescription('product')
            ->withPrice(1.0);

        $category = new Category();
        $category->withCode('category')
            ->addProduct($product);
        $margin = new GroupMargin();
        $margin->withMinimum(0.0)
            ->withMaximum(1000.0)
            ->withMargin(1.1);
        $group = new Group();
        $group->withCode('group')
            ->addCategory($category)
            ->addMargin($margin);

        $calculation = new Calculation();
        $this->assertDuplicateItems($calculation, 0);

        $calculation->addProduct($product);
        $this->assertDuplicateItems($calculation, 0);

        $calculation->addProduct($product);
        $this->assertDuplicateItems($calculation, 2);

        $calculation->removeDuplicateItems();
        $this->assertDuplicateItems($calculation, 0);
    }

    public function testEmptyItems(): void
    {
        $product = new Product();
        $product->withDescription('product')
            ->withPrice(0.0);

        $category = new Category();
        $category->withCode('category')
            ->addProduct($product);
        $margin = new GroupMargin();
        $margin->withMinimum(0.0)
            ->withMaximum(1000.0)
            ->withMargin(1.1);
        $group = new Group();
        $group->withCode('group')
            ->addCategory($category)
            ->addMargin($margin);

        $calculation = new Calculation();
        $this->assertEmptyItems($calculation, 0);

        $calculation->addProduct($product);
        $this->assertEmptyItems($calculation, 1);

        $calculation->addProduct($product, 0.0);
        $this->assertEmptyItems($calculation, 2);

        $calculation->removeEmptyItems();
        $this->assertEmptyItems($calculation, 0);
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
        self::assertSame(1.0, $calculation->groupsMargin);
        self::assertSame(0.0, $calculation->groupsMarginAmount);
        self::assertSame(0.0, $calculation->groupsTotal);

        self::assertSame(0.0, $calculation->itemsTotal);
        self::assertSame(0, $calculation->itemsCount);

        self::assertSame(0.0, $calculation->overallMargin);
        self::assertSame(0.0, $calculation->overallMarginAmount);
        self::assertSame(0.0, $calculation->overallTotal);

        self::assertSame(0.0, $calculation->totalNet);

        self::assertSame(0.0, $calculation->userMargin);
        self::assertSame(0.0, $calculation->userMarginAmount);
        self::assertSame(0.0, $calculation->userMarginTotal);

        self::assertSame(1.0, $calculation->groupsMargin);
        self::assertSame(0.0, $calculation->groupsMarginAmount);

        self::assertSame(0.0, $calculation->globalMargin);
        self::assertSame(0.0, $calculation->globalMarginAmount);

        self::assertFalse($calculation->hasDuplicateItems());
        self::assertFalse($calculation->hasEmptyItems());
        self::assertFalse($calculation->isSortable());

        self::assertTrue($calculation->isEditable());
        self::assertTrue($calculation->isEmpty());

        $date = new DatePoint();
        $calculation->withDate($date);
        self::assertSame($date, $calculation->date);

        $description = 'description';
        self::assertNull($calculation->description);
        $calculation->withDescription($description);
        self::assertSame($description, $calculation->description);

        $customer = 'customer';
        $calculation->withCustomer($customer);
        self::assertSame($customer, $calculation->customer);

        $calculation->removeEmptyItems();
        self::assertFalse($calculation->hasEmptyItems());
        $calculation->removeDuplicateItems();
        self::assertFalse($calculation->hasDuplicateItems());

        $calculation->withUserMargin(100);
        self::assertSame(100.0, $calculation->userMargin);

        $calculation->withItemsTotal(100.0);
        self::assertSame(100.0, $calculation->itemsTotal);

        $calculation->withOverallTotal(200.0);
        self::assertSame(200.0, $calculation->overallTotal);
    }

    public function testFormattedDate(): void
    {
        $date = new DatePoint('2000-01-01');
        $calculation = new Calculation();
        $calculation->withDate($date);
        $actual = $calculation->formattedDate;
        $expected = FormatUtils::formatDate($date);
        self::assertSame($expected, $actual);
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
        $calculation->withDescription('my description')
            ->withState($this->getState());
        $this->validate($calculation, 1);
    }

    public function testInvalidDescription(): void
    {
        $calculation = new Calculation();
        $calculation->withCustomer('my customer')
            ->withState($this->getState());
        $results = $this->validate($calculation, 1);
        $this->validatePaths($results, 'description');
    }

    public function testInvalidState(): void
    {
        $calculation = new Calculation();
        $calculation->withDescription('my description')
            ->withCustomer('my customer');
        $results = $this->validate($calculation, 1);
        $this->validatePaths($results, 'state');
    }

    public function testMarginBelow(): void
    {
        $calculation = new Calculation();
        self::assertFalse($calculation->isMarginBelow(0.0));

        $product = new Product();
        $product->withDescription('product1')
            ->withPrice(1.0);

        $category = new Category();
        $category->withCode('category')
            ->addProduct($product);

        $margin = new GroupMargin();
        $margin->withMinimum(0.0)
            ->withMaximum(1000.0)
            ->withMargin(1.1);
        $group = new Group();
        $group->withCode('group')
            ->addCategory($category);

        $calculation->addProduct($product);
        $calculation->withItemsTotal(100);
        $calculation->withOverallTotal(130);
        self::assertTrue($calculation->isMarginBelow(3.0));
    }

    public function testOverallMarginAmount(): void
    {
        $calculation = new Calculation();
        $calculation->withItemsTotal(100.0);
        $calculation->withOverallTotal(200.0);
        self::assertSame(100.0, $calculation->overallMarginAmount);
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
        self::assertCount(1, $calculation->groups);

        $calculation->removeEmptyItems();
        self::assertEmpty($calculation->groups);
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
        self::assertCount(1, $calculation->groups);

        $item->withCategory(null);
        $calculation->removeEmptyItems();
        self::assertCount(1, $calculation->groups);
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
        self::assertCount(1, $calculation->groups);

        $category->withGroup(null);
        $calculation->removeEmptyItems();
        self::assertCount(1, $calculation->groups);
    }

    public function testRemoveGroup(): void
    {
        $calculation = new Calculation();
        self::assertEmpty($calculation->groups);
        $group = new CalculationGroup();
        $calculation->removeGroup($group);
        self::assertEmpty($calculation->groups);
    }

    public function testSortable(): void
    {
        $group = new CalculationGroup();
        $group->withCode('group');
        $calculation = new Calculation();
        $calculation->addGroup($group);
        self::assertFalse($calculation->isSortable());
        $calculation->sort();
    }

    public function testSortCategories(): void
    {
        $group = new CalculationGroup();
        $category1 = new CalculationCategory();
        $category1->withCode('category1');

        $item1 = new CalculationItem();
        $item1->withDescription('description1');
        $category1->addItem($item1);

        $item2 = new CalculationItem();
        $item2->withDescription('description2');
        $category1->addItem($item2);

        $category2 = new CalculationCategory();
        $category2->withCode('category2');

        $group->addCategory($category1);
        $group->addCategory($category2);

        $category1->position = -1;
        $category2->position = -1;

        $actual = $group->sort();
        self::assertTrue($actual);
    }

    public function testState(): void
    {
        $calculation = new Calculation();
        self::assertNull($calculation->state);
        self::assertNull($calculation->stateId);
        self::assertNull($calculation->stateCode);
        self::assertNull($calculation->stateColor);

        $calculation->withState($this->getState());
        self::assertNotNull($calculation->state);
        self::assertSame(1, $calculation->stateId);
        self::assertSame('code', $calculation->stateCode);
        self::assertSame('color', $calculation->stateColor);
    }

    public function testValid(): void
    {
        $calculation = new Calculation();
        $calculation->withDescription('my description')
            ->withCustomer('my customer')
            ->withState($this->getState());
        $this->validate($calculation);
    }

    private function assertDuplicateItems(Calculation $calculation, int $expectedCount): void
    {
        self::assertSame(0 !== $expectedCount, $calculation->hasDuplicateItems());
        self::assertCount($expectedCount, $calculation->getDuplicateItems());
    }

    private function assertEmptyItems(Calculation $calculation, int $expectedCount): void
    {
        self::assertSame(0 !== $expectedCount, $calculation->hasEmptyItems());
        self::assertCount($expectedCount, $calculation->getEmptyItems());
    }

    private function getState(): CalculationState
    {
        $state = new CalculationState();
        $state->withCode('code')
            ->withColor('color');

        return self::setId($state);
    }
}
