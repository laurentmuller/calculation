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

use Doctrine\DBAL\Driver\Result;
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
 *
 * @author Laurent Muller
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
        $rsm = $this->_rsm;
        $stmt = $this->_stmt;

        if (!isset($rsm->indexByMap['scalars']) && $stmt instanceof Result) {
            return $stmt->fetchFirstColumn();
        }

        if (!$result = parent::hydrateAllData()) {
            return $result;
        }

        $indexColumn = $rsm->scalarMappings[$rsm->indexByMap['scalars']];
        $keys = \array_keys(\reset($result));
        $column = isset($keys[1]) && $keys[0] === $indexColumn ? $keys[1] : $keys[0];

        return \array_column($result, $column, $indexColumn);
    }
}
