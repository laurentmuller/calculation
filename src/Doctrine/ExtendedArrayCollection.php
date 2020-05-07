<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
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

            return $leftValue <=> $rightValue;
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
     * Gets the propery accessor used to sort this collection.
     *
     * @return PropertyAccessorInterface the propery accessor
     */
    protected function getPropertyAccessor(): PropertyAccessorInterface
    {
        return PropertyAccess::createPropertyAccessorBuilder()->enableMagicCall()->getPropertyAccessor();
    }
}
