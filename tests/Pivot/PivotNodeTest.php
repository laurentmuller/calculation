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

namespace App\Tests\Pivot;

use App\Interfaces\SortModeInterface;
use App\Pivot\Aggregator\AbstractAggregator;
use App\Pivot\Aggregator\CountAggregator;
use App\Pivot\PivotNode;
use PHPUnit\Framework\TestCase;

class PivotNodeTest extends TestCase
{
    public function testAdd(): void
    {
        $aggregator = new CountAggregator();
        $node = $this->createNode($aggregator);
        $actual = $node->add($aggregator);
        self::assertSame($aggregator, $actual->getAggregator());
        $actual = $node->getChildren();
        self::assertCount(1, $actual);
    }

    public function testAddNode(): void
    {
        $aggregator = new CountAggregator();
        $node = $this->createNode($aggregator);
        $child = $this->createChildNode($node);
        $actual = $node->getChildren();
        self::assertCount(1, $actual);
        self::assertSame($child, $node->getChild(0));
        self::assertNull($node->getChild(10));
    }

    public function testAddValue(): void
    {
        $parent = $this->createNode();
        $this->createChildNode($parent);

        $parent->addValue(10.0);
        self::assertCount(1, $parent);
    }

    public function testConstructor(): void
    {
        $aggregator = new CountAggregator();
        $node = $this->createNode($aggregator);
        self::assertSame($aggregator, $node->getAggregator());
        self::assertSame(0, $node->getValue());
        self::assertSame('key', $node->getKey());
        self::assertFalse($node->isTitle());

        $actual = $node->getChildren();
        self::assertCount(0, $actual);
        self::assertNull($node->getParent());
        self::assertSame('key', $node->getTitle());
        self::assertSame([], $node->getTitles());
        self::assertSame(SortModeInterface::SORT_ASC, $node->getSortMode());
    }

    public function testEqualsKey(): void
    {
        $node = $this->createNode();
        self::assertFalse($node->equalsKey(''));
        self::assertFalse($node->equalsKey(null));
        self::assertTrue($node->equalsKey('key'));
    }

    public function testEqualsKeys(): void
    {
        $parent = $this->createNode();
        self::assertTrue($parent->equalsKeys([]));
        self::assertFalse($parent->equalsKeys(['fake']));

        $child = $this->createChildNode($parent);
        self::assertTrue($child->equalsKeys(['child']));
        self::assertFalse($child->equalsKeys(['fake']));
    }

    public function testFind(): void
    {
        $parent = $this->createNode();
        $actual = $parent->find('fake');
        self::assertNull($actual);

        $this->createChildNode($parent);
        $actual = $parent->find('child');
        self::assertNotNull($actual);
    }

    public function testFIndByKeys(): void
    {
        $parent = $this->createNode();
        $actual = $parent->findByKeys(['fake']);
        self::assertNull($actual);

        $this->createChildNode($parent);
        $actual = $parent->findByKeys(['child']);
        self::assertNotNull($actual);
    }

    public function testFindRecursive(): void
    {
        $parent = $this->createNode();
        $actual = $parent->findRecursive('fake');
        self::assertNull($actual);

        $child = $this->createChildNode($parent);
        $actual = $parent->findRecursive('child');
        self::assertNotNull($actual);

        $this->createChildNode($child, 'sub-child');
        $actual = $parent->findRecursive('sub-child');
        self::assertNotNull($actual);
    }

    public function testGetChildrenAtLevel(): void
    {
        $parent = $this->createNode();
        $actual = $parent->getChildrenAtLevel(0);
        self::assertSame([$parent], $actual);

        $child = $this->createChildNode($parent);
        $actual = $parent->getChildrenAtLevel(1);
        self::assertSame([$child], $actual);

        $this->createChildNode($child, 'sub-child');
        $actual = $parent->getChildrenAtLevel(1);
        self::assertSame([$child], $actual);
    }

    public function testGetKeys(): void
    {
        $parent = $this->createNode();
        $actual = $parent->getKeys();
        self::assertSame([], $actual);

        $child = $this->createChildNode($parent);
        $actual = $child->getKeys();
        self::assertSame(['child'], $actual);

        $subChild = $this->createChildNode($child, 'sub-child');
        $actual = $subChild->getKeys();
        self::assertSame(['child', 'sub-child'], $actual);
    }

    public function testGetLastChildren(): void
    {
        $parent = $this->createNode();
        $actual = $parent->getLastChildren();
        self::assertSame([$parent], $actual);

        $child = $this->createChildNode($parent);
        $actual = $parent->getLastChildren();
        self::assertSame([$child], $actual);

        $subChild1 = $this->createChildNode($child, 'sub-child1');
        $subChild2 = $this->createChildNode($child, 'sub-child2');

        $actual = $parent->getLastChildren();
        self::assertSame([$subChild1, $subChild2], $actual);
    }

    public function testGetMaxLevel(): void
    {
        $parent = $this->createNode();
        $actual = $parent->getMaxLevel();
        self::assertSame(0, $actual);

        $child = $this->createChildNode($parent);
        $actual = $child->getMaxLevel();
        self::assertSame(0, $actual);

        $actual = $parent->getMaxLevel();
        self::assertSame(1, $actual);

        $subChild = $this->createChildNode($child, 'sub-child');
        $actual = $subChild->getMaxLevel();
        self::assertSame(0, $actual);

        $actual = $child->getMaxLevel();
        self::assertSame(1, $actual);

        $actual = $parent->getMaxLevel();
        self::assertSame(2, $actual);
    }

    public function testGetPath(): void
    {
        $parent = $this->createNode();
        $actual = $parent->getPath();
        self::assertSame('', $actual);

        $child = $this->createChildNode($parent);
        $actual = $child->getPath();
        self::assertSame('child', $actual);

        $subChild = $this->createChildNode($child, 'sub-child');
        $actual = $subChild->getPath('/');
        self::assertSame('child/sub-child', $actual);
    }

    public function testGetTitles(): void
    {
        $aggregator = new CountAggregator();
        $parent = $this->createNode($aggregator);
        $actual = $parent->getTitles();
        self::assertSame([], $actual);

        $child = $this->createChildNode($parent);
        $actual = $child->getTitles();
        self::assertSame(['child'], $actual);

        $subChild = $this->createChildNode($child, 'sub-child');
        $actual = $subChild->getTitles();
        self::assertSame(['child', 'sub-child'], $actual);
    }

    public function testIndex(): void
    {
        $parent = $this->createNode();
        $actual = $parent->index();
        self::assertSame(-1, $actual);

        $child = $this->createChildNode($parent);
        $actual = $child->index();
        self::assertSame(0, $actual);

        $subChild1 = $this->createChildNode($child, 'sub-child1');
        $subChild2 = $this->createChildNode($child, 'sub-child2');
        $actual = $subChild1->index();
        self::assertSame(0, $actual);
        $actual = $subChild2->index();
        self::assertSame(1, $actual);
    }

    public function testIsLeaf(): void
    {
        $parent = $this->createNode();
        self::assertTrue($parent->isLeaf());

        $child = $this->createChildNode($parent);
        self::assertFalse($parent->isLeaf());
        self::assertTrue($child->isLeaf());
    }

    public function testJsonSerialize(): void
    {
        $node = $this->createNode();
        $actual = $node->jsonSerialize();
        self::assertIsArray($actual);
        self::assertCount(1, $actual);

        $node->setTitle('title');
        $actual = $node->jsonSerialize();
        self::assertIsArray($actual);
        self::assertCount(2, $actual);
    }

    public function testSetParent(): void
    {
        $aggregator = new CountAggregator();
        $parent = $this->createNode($aggregator);
        $child = $this->createChildNode($parent);
        self::assertSame($parent, $child->getParent());
    }

    public function testSetParentWithException(): void
    {
        self::expectException(\InvalidArgumentException::class);
        $node = $this->createNode();
        $node->setParent($node);
    }

    public function testSetSortMode(): void
    {
        $node = $this->createNode();
        $actual = $node->getSortMode();
        self::assertSame(SortModeInterface::SORT_ASC, $actual);

        $node->setSortMode(SortModeInterface::SORT_DESC);
        $actual = $node->getSortMode();
        self::assertSame(SortModeInterface::SORT_DESC, $actual);

        $node->setSortMode(SortModeInterface::SORT_DESC);
        $actual = $node->getSortMode();
        self::assertSame(SortModeInterface::SORT_DESC, $actual);

        $node->setSortMode('fake');
        $actual = $node->getSortMode();
        self::assertSame(SortModeInterface::SORT_DESC, $actual);
    }

    public function testSetTitle(): void
    {
        $node = $this->createNode();
        self::assertSame('key', $node->getTitle());
        $node->setTitle('title');
        self::assertSame('title', $node->getTitle());
    }

    public function testSortAscending(): void
    {
        $parent = $this->createNode();
        $parent->setSortMode(SortModeInterface::SORT_ASC);
        $child = $this->createChildNode($parent);
        self::assertSame($parent, $child->getParent());
    }

    public function testSortDescending(): void
    {
        $parent = $this->createNode();
        $parent->setSortMode(SortModeInterface::SORT_DESC);
        $child = $this->createChildNode($parent);
        self::assertSame($parent, $child->getParent());
    }

    public function testToString(): void
    {
        $node = $this->createNode();
        $actual = (string) $node;
        self::assertSame('PivotNode(0)', $actual);
    }

    private function createChildNode(PivotNode $parent, string $key = 'child'): PivotNode
    {
        $node = $this->createNode($parent->getAggregator(), $key);
        $parent->addNode($node);

        return $node;
    }

    private function createNode(?AbstractAggregator $aggregator = null, string $key = 'key'): PivotNode
    {
        $aggregator ??= new CountAggregator();

        return new PivotNode($aggregator, $key);
    }
}
