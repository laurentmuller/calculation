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

namespace App\Pivot;

use App\Pivot\Aggregator\AggregatorInterface;
use App\Traits\ArrayTrait;
use App\Utils\StringUtils;

/**
 * Represents a pivot node.
 */
class PivotNode extends AbstractPivotAggregator implements \Countable, \Stringable
{
    use ArrayTrait;

    /**
     * The children.
     *
     * @var array<int|string, PivotNode>
     */
    private array $children = [];

    /** The parent node. */
    private ?PivotNode $parent = null;

    /** The title. */
    private ?string $title = null;

    /**
     * @param AggregatorInterface                $aggregator the aggregator function
     * @param string|int                         $key        the key
     * @param AggregatorInterface|int|float|null $value      the initial value
     */
    public function __construct(AggregatorInterface $aggregator, private readonly string|int $key = '', AggregatorInterface|int|float|null $value = null)
    {
        parent::__construct($aggregator, $value);
    }

    #[\Override]
    public function __toString(): string
    {
        return \sprintf('%s()', StringUtils::getShortName($this));
    }

    /**
     * Creates a new node and add it to this list of children.
     *
     * @param AggregatorInterface $aggregator the aggregator function
     * @param string|int          $key        the key
     * @param int|float|null      $value      the initial value
     *
     * @return self the newly created node
     */
    public function add(AggregatorInterface $aggregator, string|int $key = '', int|float|null $value = null): self
    {
        $node = new self($aggregator, $key, $value);
        $this->addNode($node);

        return $node;
    }

    /**
     * Adds a child to this list of children.
     *
     * <b>NB:</b> The children are sorted after insertion.
     *
     * @param PivotNode $child the child to add
     */
    public function addNode(self $child): self
    {
        $this->children[$child->getKey()] = $child;
        $child->setParent($this);
        if ($this->count() > 1) {
            \ksort($this->children, \SORT_NATURAL | \SORT_FLAG_CASE);
        }

        return $this;
    }

    #[\Override]
    public function addValue(AggregatorInterface|int|float|null $value): self
    {
        parent::addValue($value);

        return $this->update();
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->children);
    }

    /**
     * Returns if the given key is equal to this key.
     *
     * @param string|int $key the key to compare to
     *
     * @return bool true if equal
     */
    public function equalsKey(string|int $key): bool
    {
        return $key === $this->key;
    }

    /**
     * Returns if the given keys are equal to these keys.
     *
     * @param array<string|int> $keys the keys to compare to
     *
     * @return bool true if equal
     *
     * @see PivotNode::getKeys()
     */
    public function equalsKeys(array $keys): bool
    {
        return $keys === $this->getKeys();
    }

    /**
     * Finds a child for the given key.
     *
     * @param string|int $key the node key to search for
     */
    public function find(string|int $key): ?self
    {
        return $this->children[$key] ?? null;
    }

    /**
     * Finds a child node for the given array of keys.
     *
     * @param array<string|int> $keys the keys to search for
     *
     * @return ?self the child node, if found; null otherwise
     */
    public function findByKeys(array $keys): ?self
    {
        $current = $this;
        foreach ($keys as $key) {
            $found = $current->find($key);
            if (!$found instanceof self) {
                return null;
            }
            $current = $found;
        }

        return $current;
    }

    /**
     * Gets the children.
     *
     * @return array<int|string, PivotNode>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Gets the key.
     */
    public function getKey(): string|int
    {
        return $this->key;
    }

    /**
     * Gets the keys.
     *
     * @return array<string|int> the keys or an empty array if this node is the root node
     */
    public function getKeys(): array
    {
        return $this->upToRoot(static fn (PivotNode $node): string|int => $node->key);
    }

    /**
     * Gets the leaf nodes; include this instance if applicable.
     *
     * @return PivotNode[] the leaf nodes
     */
    public function getLeafNodes(): array
    {
        return $this->filter(static fn (PivotNode $node): bool => $node->isLeaf());
    }

    /**
     * Gets this level (0 for root, 1 for first level, etc...).
     */
    public function getLevel(): int
    {
        if ($this->isRoot()) {
            return 0;
        }

        return 1 + $this->parent->getLevel();
    }

    /**
     * Gets the maximum level.
     */
    public function getMaxLevel(): int
    {
        $level = 0;
        $node = $this;
        while (!$node->isLeaf()) {
            $node = \array_first($node->children);
            ++$level;
        }

        return $level;
    }

    /**
     * Gets nodes for the given level; include this instance if applicable.
     *
     * @param int $level the level to get the nodes for
     *
     * @return PivotNode[]
     */
    public function getNodesAtLevel(int $level): array
    {
        return $this->filter(static fn (PivotNode $node): bool => $node->getLevel() === $level);
    }

    /**
     * Gets the parent's node.
     *
     * @return ?self the parent's node or null if the root
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * Gets the imploded path.
     *
     * @return string the path or an empty string if this node is the root node
     */
    public function getPath(string $separator = PivotTable::PATH_SEPARATOR): string
    {
        if ($this->isRoot()) {
            return '';
        }

        return \implode(
            $separator,
            \array_map(static fn (string|int $value): string => (string) $value, $this->getKeys())
        );
    }

    /**
     * Gets the title.
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Gets the titles.
     *
     * @return string[] the titles or an empty array if this node is the root node
     */
    public function getTitles(): array
    {
        return $this->upToRoot(static fn (PivotNode $node): string => $node->getTitle() ?? '');
    }

    /**
     * Gets the zero-based index (position) in the parent's children.
     *
     * @return int the index, if parent is set; -1 otherwise
     */
    public function index(): int
    {
        if ($this->isRoot()) {
            return -1;
        }

        $index = \array_search(
            $this->key,
            \array_keys($this->parent->getChildren()),
            true
        );

        return \is_int($index) ? $index : -1;
    }

    /**
     * Returns if this node is a leaf node.
     *
     * A leaf node is a node without children.
     *
     * @return bool true if leaf
     *
     * @phpstan-assert-if-false non-empty-array $this->children
     */
    public function isLeaf(): bool
    {
        return [] === $this->children;
    }

    /**
     * Returns if this node is a root node.
     *
     * A root node is a node without a parent.
     *
     * @return bool true if root
     *
     * @phpstan-assert-if-true null $this->parent
     */
    public function isRoot(): bool
    {
        return !$this->parent instanceof self;
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return \array_filter([
            'key' => $this->key,
            'title' => $this->title,
            'value' => $this->aggregator->getRoundResult(),
            'children' => $this->children,
        ]);
    }

    /**
     * Sets the parent's node.
     *
     * @throws \InvalidArgumentException if the parent is this instance
     */
    public function setParent(?self $parent): self
    {
        if ($parent === $this) {
            throw new \InvalidArgumentException('The parent is invalid (same instance).');
        }
        $this->parent = $parent;

        return $this;
    }

    /**
     * Sets the title.
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Update this value with of all these children (if any).
     *
     * <b>NB:</b> This method is called recursively for the parents (if any).
     */
    protected function update(): self
    {
        if (!$this->isLeaf()) {
            $this->aggregator->initialize();
            foreach ($this->children as $child) {
                $this->aggregator->add($child->getAggregator());
            }
        }

        $this->parent?->update();

        return $this;
    }

    /**
     * Filter this instance and all children recursively.
     *
     * @param callable(PivotNode): bool $callable the filter function
     *
     * @return PivotNode[]
     */
    private function filter(callable $callable): array
    {
        if ($callable($this)) {
            return [$this];
        }

        $result = [];
        foreach ($this->children as $child) {
            if ($callable($child)) {
                $result[] = $child;
            } else {
                $result = \array_merge($result, $child->filter($callable));
            }
        }

        return $result;
    }

    /**
     * @template T
     *
     * @param callable(PivotNode): T $callable
     *
     * @return T[]
     */
    private function upToRoot(callable $callable): array
    {
        $result = [];
        $current = $this;
        while (!$current->isRoot()) {
            \array_unshift($result, $callable($current));
            $current = $current->parent;
        }

        return $result;
    }
}
