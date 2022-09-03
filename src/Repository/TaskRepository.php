<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for task entity.
 *
 * @template-extends AbstractCategoryItemRepository<Task>
 */
class TaskRepository extends AbstractCategoryItemRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry The connections and entity managers registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * Gets the query builder for the list of tasks sorted by name.
     *
     * @param bool   $all   true to return all, false to return only tasks that contains at least one operation with one margin
     * @param string $alias the default entity alias
     *
     * @psalm-param literal-string $alias
     */
    public function getSortedBuilder(bool $all = true, string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        $field = $this->getSortField('name', $alias);
        $builder = $this->createQueryBuilder($alias)
            ->orderBy($field, Criteria::ASC);

        if (!$all) {
            $builder->innerJoin("$alias.items", 'item')
                ->innerJoin('item.margins', 'margin')
                ->groupBy($field);
        }

        return $builder;
    }
}
