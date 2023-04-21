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

namespace App\Table;

use App\Entity\Category;
use App\Entity\Group;
use App\Repository\AbstractCategoryItemRepository;
use App\Repository\CategoryRepository;
use App\Repository\GroupRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Common abstract table for products and tasks.
 *
 * @template T of \App\Entity\AbstractCategoryItemEntity
 *
 * @template-extends AbstractEntityTable<T>
 *
 * @psalm-import-type DropDownType from CategoryRepository
 */
abstract class AbstractCategoryItemTable extends AbstractEntityTable
{
    /**
     * The category parameter name (int).
     */
    final public const PARAM_CATEGORY = 'categoryId';

    /**
     * The group parameter name (int).
     */
    private const PARAM_GROUP = CategoryTable::PARAM_GROUP;

    /**
     * Constructor.
     *
     * @psalm-param AbstractCategoryItemRepository<T> $repository
     */
    public function __construct(
        AbstractCategoryItemRepository $repository,
        protected readonly CategoryRepository $categoryRepository,
        protected readonly GroupRepository $groupRepository
    ) {
        parent::__construct($repository);
    }

    /**
     * {@inheritDoc}
     */
    public function getDataQuery(Request $request): DataQuery
    {
        $query = parent::getDataQuery($request);
        $categoryId = $this->getRequestInt($request, self::PARAM_CATEGORY);
        $query->addCustomData(self::PARAM_CATEGORY, $categoryId);
        $groupId = $this->getRequestInt($request, self::PARAM_GROUP);
        $query->addCustomData(self::PARAM_GROUP, $groupId);

        return $query;
    }

    /**
     * Gets drop-down values.
     *
     * @psalm-return DropDownType
     */
    abstract protected function getDropDownValues(): array;

    /**
     * {@inheritDoc}
     */
    protected function search(DataQuery $query, QueryBuilder $builder, string $alias): bool
    {
        $result = parent::search($query, $builder, $alias);
        if (0 !== $categoryId = $query->getCustomData(self::PARAM_CATEGORY, 0)) {
            /** @psalm-var string $field */
            $field = $this->repository->getSearchFields('category.id', $alias);
            $builder->andWhere($field . '=:' . self::PARAM_CATEGORY)
                ->setParameter(self::PARAM_CATEGORY, $categoryId, Types::INTEGER);

            return true;
        }
        if (0 !== $groupId = $query->getCustomData(self::PARAM_GROUP, 0)) {
            /** @psalm-var string $field */
            $field = $this->repository->getSearchFields('group.id', $alias);
            $builder->andWhere($field . '=:' . self::PARAM_GROUP)
                ->setParameter(self::PARAM_GROUP, $groupId, Types::INTEGER);

            return true;
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        parent::updateResults($query, $results);
        if (!$query->callback) {
            $categoryId = $query->getCustomData(self::PARAM_CATEGORY, 0);
            $results->addParameter(self::PARAM_CATEGORY, $categoryId);

            $groupId = $query->getCustomData(self::PARAM_GROUP, 0);
            $results->addParameter(self::PARAM_GROUP, $groupId);

            $results->addCustomData('dropdown', $this->getDropDownValues());
            $results->addCustomData('category', $this->getCategory($categoryId));
            $results->addCustomData('group', $this->getGroup($groupId));
        }
    }

    /**
     * Gets the category data for the given identifier.
     */
    private function getCategory(int $categoryId): ?array
    {
        if (0 !== $categoryId && ($entity = $this->categoryRepository->find($categoryId)) instanceof Category) {
            return [
                'id' => $entity->getId(),
                'code' => $entity->getCode(),
            ];
        }

        return null;
    }

    /**
     * Gets the group data for the given identifier.
     */
    private function getGroup(int $groupId): ?array
    {
        if (0 !== $groupId && ($entity = $this->groupRepository->find($groupId)) instanceof Group) {
            return [
                'id' => $entity->getId(),
                'code' => $entity->getCode(),
            ];
        }

        return null;
    }
}
