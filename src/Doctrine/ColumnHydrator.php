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

use Doctrine\ORM\Internal\Hydration\ArrayHydrator;

/**
 * Returns one-dimensional scalar array from query: mixed[][] => mixed[].
 *
 * Example:
 * <pre>
 * ArrayHydrator : [['id' => 1], ['id' => 2]]
 * ColumnHydrator: [1, 2]
 * </pre>
 *
 * @see https://stackoverflow.com/questions/11657835/how-to-get-a-one-dimensional-scalar-array-as-a-doctrine-dql-query-result
 */
class ColumnHydrator extends ArrayHydrator
{
    /**
     * This hydrator name.
     */
    public const NAME = 'ColumnHydrator';

    /**
     * {@inheritdoc}
     *
     * @see \Doctrine\ORM\Internal\Hydration\AbstractHydrator::hydrateAllData()
     */
    protected function hydrateAllData(): array
    {
        if (!isset($this->_rsm->indexByMap['scalars'])) {
            return $this->_stmt->fetchFirstColumn();
        }

        if (!$result = parent::hydrateAllData()) {
            return $result;
        }

        $indexColumn = $this->_rsm->scalarMappings[$this->_rsm->indexByMap['scalars']];
        $keys = \array_keys(\reset($result));
        $column = isset($keys[1]) && $keys[0] === $indexColumn ? $keys[1] : $keys[0];

        return \array_column($result, $column, $indexColumn);
    }
}
