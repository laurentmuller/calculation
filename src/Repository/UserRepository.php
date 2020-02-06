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

use App\Entity\User;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * Repository for user entity.
 *
 * @author Laurent Muller
 *
 * @see \App\Entity\User
 */
class UserRepository extends BaseRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry The connections and entity managers registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Gets the query builder for the list of users sorted by name.
     */
    public function getSortedBuilder(): QueryBuilder
    {
        $field = (string) $this->getSortFields(self::DEFAULT_ALIAS, 'username');

        return $this->createQueryBuilder(self::DEFAULT_ALIAS)
            ->orderBy($field, Criteria::ASC);
    }
}
