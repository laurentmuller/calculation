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
     * The alias for the category entity.
     */
    private const CATEGORY_ALIAS = AbstractCategoryItemRepository::CATEGORY_ALIAS;

    /**
     * The alias for the group margin entity.
     */
    private const MARGIN_ALIAS = 'm';

    /**
     * The alias for the product entity.
     */
    private const PRODUCT_ALIAS = CategoryRepository::PRODUCT_ALIAS;
    /**
     * The alias for the task entity.
     */
    private const TASK_ALIAS = CategoryRepository::TASK_ALIAS;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }

    /**
     * Gets all group ordered by code.
     *
     * @return Group[]
     */
    public function findByCode(): array
    {
        return $this->findBy([], ['code' => self::SORT_ASC]);
    }

    /**
     * Gets groups used for the category table.
     *
     * <b>Note:</b> Only groups with at least one category are returned.
     *
     * @psalm-return array<array{id: int, code: string}>
     */
    public function getDropDown(): array
    {
        $result = $this->createQueryBuilder('g')
            ->select('g.id')
            ->addSelect('g.code')
            ->innerJoin('g.categories', 'c')
            ->groupBy('g.id')
            ->orderBy('g.code', self::SORT_ASC)
            ->getQuery()
            ->getArrayResult();

        return \count($result) > 1 ? $result : [];
    }

    /**
     * Gets the query builder for the list of groups sorted by code.
     *
     * @param literal-string $alias the entity alias
     */
    public function getSortedBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        $field = $this->getSortField('code', $alias);

        return $this->createQueryBuilder($alias)
            ->orderBy($field, self::SORT_ASC);
    }

    /**
     * Gets the query builder for the table.
     *
     * @param literal-string $alias the entity alias
     */
    public function getTableQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->select("$alias.id")
            ->addSelect("$alias.code")
            ->addSelect("$alias.description")
            ->addSelect($this->getCountDistinct(self::MARGIN_ALIAS, 'margins'))
            ->addSelect($this->getCountDistinct(self::CATEGORY_ALIAS, 'categories'))
            ->addSelect($this->getCountDistinct(self::PRODUCT_ALIAS, 'products'))
            ->addSelect($this->getCountDistinct(self::TASK_ALIAS, 'tasks'))
            ->leftJoin("$alias.margins", self::MARGIN_ALIAS)
            ->leftJoin("$alias.categories", self::CATEGORY_ALIAS)
            ->leftJoin(self::CATEGORY_ALIAS . '.products', self::PRODUCT_ALIAS)
            ->leftJoin(self::CATEGORY_ALIAS . '.tasks', self::TASK_ALIAS)
            ->groupBy("$alias.id");
    }
}
