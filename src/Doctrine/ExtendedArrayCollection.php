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

/**
 * Extends the array collection.
 *
 * @author Laurent Muller
 */
class ExtendedArrayCollection extends ArrayCollection implements ExtendedCollection
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
     * {@inheritdoc}
     */
    public function reduce(callable $callback, $initial = null)
    {
        return \array_reduce($this->toArray(), $callback, $initial);
    }
}
