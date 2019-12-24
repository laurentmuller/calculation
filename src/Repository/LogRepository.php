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

use App\Entity\Log;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Repository for log entity.
 *
 * @author Laurent Muller
 *
 * @see \App\Entity\Log
 */
class LogRepository extends BaseRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry the connections and entity managers registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Log::class);
    }

    /**
     * Gets the distinct chanels.
     *
     * @return string[]
     */
    public function getChannels(): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.channel as name')
            ->addSelect('count(e.id) as count')
            ->groupBy('e.channel')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Gets the distinct levels and level names.
     */
    public function getLevels(): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.level as name')
            ->addSelect('count(e.id) as count')
            ->groupBy('e.level')
            ->getQuery()
            ->getArrayResult();
    }
}
