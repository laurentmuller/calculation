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

namespace App\Utils;

/**
 * Simple implementation of Iterator interface for an array.
 *
 * @author Laurent Muller
 */
class ArrayIterator implements \Iterator
{
    /**
     * The array.
     *
     * @var array
     */
    protected $array;

    /**
     * Constructor.
     *
     * @param array $array the array to iterate for
     */
    public function __construct(array $array)
    {
        $this->array = $array;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return \current($this->array);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return \key($this->array);
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        return \next($this->array);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        return \reset($this->array);
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return null !== $this->key();
    }
}
