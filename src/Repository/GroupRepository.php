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

use App\Entity\Group;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for group entity.
 *
 * @template-extends AbstractRepository<Group>
 */
class GroupRepository extends AbstractRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry The connections and entity managers registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }

    /**
     * Gets all group order by code.
     *
     * @return Group[]
     */
    public function findAllByCode(): array
    {
        return $this->findBy([], ['code' => Criteria::ASC]);
    }

    /**
     * Gets groups with the number of categories.
     *
     * <b>Note:</b> Only groups with at least one category are returned.
     *
     * @return array an array with the group and the number of categories
     */
    public function getListCountCategories(): array
    {
        return $this->createQueryBuilder('g')
            ->select('g.id')
            ->addSelect('g.code')
            ->addSelect('g.description')
            ->addSelect('COUNT(c.id) as count')
            ->innerJoin('g.categories', 'c')
            ->groupBy('g.id')
            ->orderBy('g.code', Criteria::ASC)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Gets the query builder for the list of groups sorted by code.
     *
     * @param string $alias the default entity alias
     *
     * @phpstan-param literal-string $alias
     */
    public function getSortedBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        $field = $this->getSortField('code', $alias);

        return $this->createQueryBuilder($alias)
            ->orderBy($field, Criteria::ASC);
    }
}
