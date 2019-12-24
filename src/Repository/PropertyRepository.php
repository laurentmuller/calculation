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

namespace App\Repository;

use App\Entity\Property;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Repository for property entity.
 *
 * @author Laurent Muller
 *
 * @see \App\Entity\Property
 */
class PropertyRepository extends BaseRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry The connections and entity managers registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Property::class);
    }

    /**
     * Gets a property by it's name.
     *
     * @param string $name the property name to search for
     *
     * @return Property|null the property or null if the property can not be found
     */
    public function findOneByName(string $name): ?Property
    {
        return parent::findOneByName($name);
    }
}
