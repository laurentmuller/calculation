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

use App\Pivot\Aggregator\AggregatorInterface;
use App\Pivot\Aggregator\CountAggregator;
use App\Pivot\Aggregator\SumAggregator;
use App\Pivot\PivotNode;
use PHPUnit\Framework\TestCase;

final class PivotNodeTest extends TestCase
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
        $this->createChildNode($node);
        $actual = $node->getChildren();
        self::assertCount(1, $actual);
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
        self::assertSame(0, $node->getResult());
        self::assertSame(0, $node->getRoundResult());
        self::assertSame('0', $node->getFormattedValue());
        self::assertSame('key', $node->getKey());

        $actual = $node->getChildren();
        self::assertEmpty($actual);
        self::assertNull($node->getParent());
        self::assertNull($node->getTitle());
        self::assertSame([], $node->getTitles());
    }

    public function testEqualsKey(): void
    {
        $node = $this->createNode();
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

    public function testFindByKeys(): void
    {
        $parent = $this->createNode();
        $actual = $parent->findByKeys(['fake']);
        self::assertNull($actual);

        $this->createChildNode($parent);
        $actual = $parent->findByKeys(['child']);
        self::assertNotNull($actual);
    }

    public function testFloatAggregator(): void
    {
        $aggregator = new SumAggregator();
        $node = $this->createNode($aggregator);
        self::assertSame(0.0, $node->getResult());
        self::assertSame(0.00, $node->getRoundResult());
        self::assertSame('0.00', $node->getFormattedValue());
        $node->addValue(10.00);
        self::assertSame(10.00, $node->getResult());
        self::assertSame(10.00, $node->getRoundResult());
        self::assertSame('10.00', $node->getFormattedValue());
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

    public function testGetLeafNodes(): void
    {
        $parent = $this->createNode();
        $actual = $parent->getLeafNodes();
        self::assertSame([$parent], $actual);

        $child = $this->createChildNode($parent);
        $actual = $parent->getLeafNodes();
        self::assertSame([$child], $actual);

        $subChild1 = $this->createChildNode($child, 'sub-child1');
        $subChild2 = $this->createChildNode($child, 'sub-child2');

        $actual = $parent->getLeafNodes();
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

    public function testGetNodesAtLevel(): void
    {
        $parent = $this->createNode();
        $actual = $parent->getNodesAtLevel(0);
        self::assertSame([$parent], $actual);

        $child1 = $this->createChildNode($parent, 'child1');
        $actual = $parent->getNodesAtLevel(1);
        self::assertSame([$child1], $actual);

        $child2 = $this->createChildNode($child1, 'child2');
        $actual = $parent->getNodesAtLevel(2);
        self::assertSame([$child2], $actual);
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
        $child->setTitle('child');
        $actual = $child->getTitles();
        self::assertSame(['child'], $actual);

        $subChild = $this->createChildNode($child, 'sub-child');
        $subChild->setTitle('sub-child');
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

    public function testIntAggregator(): void
    {
        $aggregator = new CountAggregator();
        $node = $this->createNode($aggregator);
        self::assertSame(0, $node->getResult());
        self::assertSame(0, $node->getRoundResult());
        self::assertSame('0', $node->getFormattedValue());
        $node->addValue(10.00);
        self::assertSame(1, $node->getResult());
        self::assertSame(1, $node->getRoundResult());
        self::assertSame('1', $node->getFormattedValue());
    }

    public function testIsLeaf(): void
    {
        $parent = $this->createNode();
        self::assertTrue($parent->isRoot());
        self::assertTrue($parent->isLeaf());

        $child = $this->createChildNode($parent);
        self::assertTrue($child->isLeaf());
        self::assertFalse($child->isRoot());

        self::assertTrue($parent->isRoot());
        self::assertFalse($parent->isLeaf());
    }

    public function testJsonSerialize(): void
    {
        $node = $this->createNode();
        $actual = $node->jsonSerialize();
        self::assertCount(2, $actual);

        $node->setTitle('title');
        $actual = $node->jsonSerialize();
        self::assertCount(3, $actual);
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

    public function testSetTitle(): void
    {
        $node = $this->createNode();
        self::assertNull($node->getTitle());
        $node->setTitle('title');
        self::assertSame('title', $node->getTitle());
    }

    public function testToString(): void
    {
        $node = $this->createNode();
        $actual = (string) $node;
        self::assertSame('PivotNode()', $actual);
    }

    private function createChildNode(PivotNode $parent, string $key = 'child'): PivotNode
    {
        $node = $this->createNode(null, $key);
        $parent->addNode($node);

        return $node;
    }

    private function createNode(?AggregatorInterface $aggregator = null, string $key = 'key'): PivotNode
    {
        $aggregator ??= new CountAggregator();

        return new PivotNode($aggregator, $key);
    }
}
