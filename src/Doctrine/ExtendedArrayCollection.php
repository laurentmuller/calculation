<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Extends the array collection.
 *
 * @author Laurent Muller
 */
class ExtendedArrayCollection extends ArrayCollection implements ExtendedCollectionInterface
{
    /**
     * Initializes a new collection.
     *
     * @param array $elements the initial elements
     */
    public function __construct(array $elements = [])
    {
        parent::__construct($elements);
    }

    /**
     * Creates a new instance from the given array.
     *
     * @param array $elements the initial elements
     */
    public static function fromArray(array $elements = []): self
    {
        return new self($elements);
    }

    /**
     * Creates a new instance from the given collection.
     *
     * @param Collection $collection the collection to get initial elements
     */
    public static function fromCollection(Collection $collection): self
    {
        return new self($collection->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function getSortedCollection($field): self
    {
        $elements = $this->getSortedIterator($field)->getArrayCopy();

        return new self($elements);
    }

    /**
     * {@inheritdoc}
     */
    public function getSortedIterator($field): \ArrayIterator
    {
        /** @var \ArrayIterator $iterator */
        $iterator = $this->getIterator();
        $accessor = $this->getPropertyAccessor();

        $iterator->uasort(function ($left, $right) use ($accessor, $field) {
            $leftValue = $accessor->getValue($left, $field);
            $rightValue = $accessor->getValue($right, $field);

            return $this->compare($leftValue, $rightValue);
        });
        $list = \iterator_to_array($iterator, false);

        return new \ArrayIterator($list);
    }

    /**
     * {@inheritdoc}
     */
    public function reduce(callable $callback, $initial = null)
    {
        return \array_reduce($this->toArray(), $callback, $initial);
    }

    /**
     * Compare the given values.
     * <p>
     * <b>Note:</b> if both values are string, the {@link http://www.php.net/manual/en/function.strnatcasecmp.php strnatcasecmp} function is used.
     * </p>.
     *
     * @param mixed $value1 the first value to compare
     * @param mixed $value2 the second value to compare
     *
     * @return int returns &lt; 0 if value1 is less than value2; &gt; 0 if value1 is greater than value2 and 0 if both values are equal
     */
    protected function compare($value1, $value2): int
    {
        if (\is_string($value1) && \is_string($value2)) {
            return \strnatcasecmp($value1, $value2);
        }

        return $value1 <=> $value2;
    }

    /**
     * Gets the propery accessor used to sort this collection.
     *
     * @return PropertyAccessorInterface the propery accessor
     */
    protected function getPropertyAccessor(): PropertyAccessorInterface
    {
        return PropertyAccess::createPropertyAccessorBuilder()->enableMagicCall()->getPropertyAccessor();
    }
}
