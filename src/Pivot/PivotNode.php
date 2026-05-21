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

use App\Interfaces\SortModeInterface;
use App\Pivot\Aggregator\AbstractAggregator;
use App\Traits\ArrayTrait;
use App\Utils\StringUtils;

/**
 * Represents a pivot node.
 */
class PivotNode extends AbstractPivotAggregator implements \Countable, \Stringable, SortModeInterface
{
    use ArrayTrait;

    /**
     * The children.
     *
     * @var array<int, PivotNode>
     */
    private array $children = [];

    /** The parent node. */
    private ?PivotNode $parent = null;

    /**
     * The sort direction.
     *
     * @phpstan-var self::SORT_*
     */
    private string $sortMode = self::SORT_ASC;

    /** The title. */
    private ?string $title = null;

    /**
     * @param AbstractAggregator $aggregator the aggregator function
     * @param mixed              $key        the key
     * @param mixed              $value      the initial value
     */
    public function __construct(AbstractAggregator $aggregator, private readonly mixed $key = null, mixed $value = null)
    {
        parent::__construct($aggregator, $value);
    }

    #[\Override]
    public function __toString(): string
    {
        return \sprintf('%s(%s)', StringUtils::getShortName($this), 0);
    }

    /**
     * Creates a new node and add it to this list of children.
     *
     * @param AbstractAggregator $aggregator the aggregator function
     * @param mixed              $key        the key
     * @param mixed              $value      the initial value
     *
     * @return self the newly created node
     */
    public function add(AbstractAggregator $aggregator, mixed $key = null, mixed $value = null): self
    {
        $node = new self($aggregator, $key, $value);
        $this->addNode($node);

        return $node;
    }

    /**
     * Adds a child to the list of children.
     *
     * <b>NB:</b> The children are sorted after insertion.
     *
     * @param PivotNode $child the child to add
     */
    public function addNode(self $child): self
    {
        $this->children[] = $child;
        $child->setParent($this);

        return $this->sort();
    }

    #[\Override]
    public function addValue(mixed $value): static
    {
        parent::addValue($value);

        return $this->update();
    }

    /**
     * Gets the number of children.
     */
    #[\Override]
    public function count(): int
    {
        return \count($this->children);
    }

    /**
     * Returns if the given key is the same as this key.
     *
     * @param mixed $key the key to compare to
     *
     * @return bool true if equal
     */
    public function equalsKey(mixed $key): bool
    {
        return $key === $this->key;
    }

    /**
     * Returns if the given keys are the same as these keys.
     *
     * @param array $keys the keys to compare to
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
     * Finds a child node for the given key.
     *
     * @param mixed $key the node key to search for
     *
     * @return self|null the child node, if found; null otherwise
     */
    public function find(mixed $key): ?self
    {
        return $this->findFirst(
            $this->children,
            static fn (PivotNode $child): bool => $child->equalsKey($key)
        );
    }

    /**
     * Finds a child node for the given array of keys.
     *
     * @param array $keys the node keys to search for
     *
     * @return self|null the child node, if found; null otherwise
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
     * Recursively finds a child node for the given key.
     *
     * @param mixed $key the node key to search for
     *
     * @return self|null the node, if found; null otherwise
     */
    public function findRecursive(mixed $key): ?self
    {
        foreach ($this->children as $child) {
            if ($child->equalsKey($key)) {
                return $child;
            }
            $found = $child->findRecursive($key);
            if ($found instanceof self) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Gets the child at the given index (position).
     *
     * @param int $index the index
     *
     * @return self|null the child, if index is valid; null otherwise
     */
    public function getChild(int $index): ?self
    {
        if ($index >= 0 && $index < $this->count()) {
            return $this->children[\array_keys($this->children)[$index]];
        }

        return null;
    }

    /**
     * Gets the children.
     *
     * @return PivotNode[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Gets all children for the given level.
     *
     * @param int $level the level
     *
     * @return PivotNode[]
     */
    public function getChildrenAtLevel(int $level): array
    {
        if ($this->getLevel() === $level) {
            return [$this];
        }

        $result = [];
        foreach ($this->children as $child) {
            if ($child->getLevel() === $level) {
                $result[] = $child;
            } else {
                $result = \array_merge($result, $child->getChildrenAtLevel($level));
            }
        }

        return $result;
    }

    /**
     * Gets the key.
     */
    public function getKey(): mixed
    {
        return $this->key;
    }

    /**
     * Gets the keys.
     *
     * @return array the keys or an empty array if this node is the root node
     */
    public function getKeys(): array
    {
        return $this->upToRoot(static fn (PivotNode $node): mixed => $node->key);
    }

    /**
     * Gets all children from the last level.
     *
     * @return PivotNode[]
     */
    public function getLastChildren(): array
    {
        if ($this->isLeaf()) {
            return [$this];
        }

        $result = [];
        foreach ($this->children as $child) {
            if ($child->isLeaf()) {
                $result[] = $child;
            } else {
                $result = \array_merge($result, $child->getLastChildren());
            }
        }

        return $result;
    }

    /**
     * Gets the level (0 for root, 1 for first level, etc...).
     *
     * @return int the level
     */
    public function getLevel(): int
    {
        if ($this->isRoot()) {
            return 0;
        }

        return 1 + $this->parent->getLevel();
    }

    /**
     * Gets the maximum deep level.
     *
     * @return int the deep level
     */
    public function getMaxLevel(): int
    {
        $level = 0;
        $node = $this;
        while (!$node->isLeaf()) {
            ++$level;
            $node = $node->children[0];
        }

        return $level;
    }

    /**
     * Gets the parent's node.
     *
     * @return self|null the parent's node or null if the root
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * Gets the path.
     */
    public function getPath(string $separator = PivotTable::PATH_SEPARATOR): string
    {
        if ($this->isRoot()) {
            return '';
        }

        return \implode(
            $separator,
            \array_map(static fn (mixed $value): string => (string) $value, $this->getKeys())
        );
    }

    /**
     * Gets the sort mode.
     *
     * @return self::SORT_*
     */
    public function getSortMode(): string
    {
        return $this->sortMode;
    }

    /**
     * Gets the title.
     */
    public function getTitle(): ?string
    {
        return $this->title ?? (string) $this->key;
    }

    /**
     * Gets the titles.
     *
     * @return string[] the titles or an empty array if this node is the root node
     */
    public function getTitles(): array
    {
        return $this->upToRoot(static fn (PivotNode $node): string => (string) $node->getTitle());
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
        $index = 0;
        foreach ($this->parent->getChildren() as $child) {
            if ($child === $this) {
                return $index;
            }
            ++$index;
        }

        return -1;
    }

    /**
     * Returns if this node is a leaf node.
     *
     * A leaf node is a node without children.
     *
     * @return bool true if leaf
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
            'value' => $this->aggregator->getFormattedResult(),
            'children' => [] === $this->children ? null : $this->children,
        ]);
    }

    /**
     * Sets the parent's node.
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
     * Sets the sort mode and sort values if different from the current sort mode.
     *
     * @param self::SORT_* $sortMode the sort mode to set
     */
    public function setSortMode(string $sortMode): self
    {
        if ($this->sortMode === $sortMode) {
            return $this;
        }
        $this->sortMode = $sortMode;

        return $this->sort();
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
    protected function update(): static
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
     * Sort children.
     */
    private function sort(): self
    {
        if ($this->count() <= 1) {
            return $this;
        }

        if (self::SORT_ASC === $this->sortMode) {
            \uasort($this->children, static fn (self $left, self $right): int => $left->getKey() <=> $right->getKey());
        } else {
            \uasort($this->children, static fn (self $left, self $right): int => $right->getKey() <=> $left->getKey());
        }

        return $this;
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
