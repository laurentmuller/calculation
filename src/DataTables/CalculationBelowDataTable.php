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

namespace App\DataTables;

use App\Repository\BaseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;

/**
 * Data table handler for calculation with margin below the minimum.
 *
 * @author Laurent Muller
 */
class CalculationBelowDataTable extends CalculationDataTable
{
    /**
     * The datatable identifier.
     */
    public const ID = 'Calculation.below';

    /**
     * {@inheritdoc}
     */
    protected function createQueryBuilder($alias = BaseRepository::DEFAULT_ALIAS): QueryBuilder
    {
        $param = 'minMargin';
        $itemsField = "{$alias}.itemsTotal";
        $overallField = "{$alias}.overallTotal";
        $minMargin = $this->getApplication()->getMinMargin();

        return parent::createQueryBuilder($alias)
            ->andWhere("{$itemsField} != 0")
            ->andWhere("({$overallField} / {$itemsField}) - 1 < :{$param}")
            ->setParameter($param, $minMargin, Types::FLOAT);
    }
}
