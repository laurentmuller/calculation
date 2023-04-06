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
     * Gets groups used by category table.
     *
     * <b>Note:</b> Only groups with at least one category are returned.
     *
     * @psalm-return array<array{id: int, code: string}>
     */
    public function getDropDown(): array
    {
        return $this->createQueryBuilder('g')
            ->select('g.id')
            ->addSelect('g.code')
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
     * @psalm-param literal-string $alias
     */
    public function getSortedBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        $field = $this->getSortField('code', $alias);

        return $this->createQueryBuilder($alias)
            ->orderBy($field, Criteria::ASC);
    }
}
