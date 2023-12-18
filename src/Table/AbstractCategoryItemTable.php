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

use App\Entity\AbstractCategoryItemEntity;
use App\Entity\Category;
use App\Entity\Group;
use App\Repository\AbstractCategoryItemRepository;
use App\Repository\CategoryRepository;
use App\Repository\GroupRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;

/**
 * Common abstract table for products and tasks.
 *
 * @template TEntity of AbstractCategoryItemEntity
 * @template TRepository of AbstractCategoryItemRepository<TEntity>
 *
 * @template-extends AbstractEntityTable<TEntity, TRepository>
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
     * @psalm-param TRepository $repository
     */
    public function __construct(
        AbstractCategoryItemRepository $repository,
        protected readonly CategoryRepository $categoryRepository,
        protected readonly GroupRepository $groupRepository
    ) {
        parent::__construct($repository);
    }

    /**
     * Gets drop-down values.
     *
     * @psalm-return DropDownType
     */
    abstract protected function getDropDownValues(): array;

    protected function search(DataQuery $query, QueryBuilder $builder, string $alias): bool
    {
        $repository = $this->getRepository();
        $result = parent::search($query, $builder, $alias);
        $categoryId = $query->customData->categoryId;
        if (0 !== $categoryId) {
            /** @psalm-var string $field */
            $field = $repository->getSearchFields('category.id', $alias);
            $builder->andWhere($field . '=:' . self::PARAM_CATEGORY)
                ->setParameter(self::PARAM_CATEGORY, $categoryId, Types::INTEGER);

            return true;
        }
        $groupId = $query->customData->groupId;
        if (0 !== $groupId) {
            /** @psalm-var string $field */
            $field = $repository->getSearchFields('group.id', $alias);
            $builder->andWhere($field . '=:' . self::PARAM_GROUP)
                ->setParameter(self::PARAM_GROUP, $groupId, Types::INTEGER);

            return true;
        }

        return $result;
    }

    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        parent::updateResults($query, $results);
        if (!$query->callback) {
            $categoryId = $query->customData->categoryId;
            $results->addParameter(self::PARAM_CATEGORY, $categoryId);

            $groupId = $query->customData->groupId;
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
        if (0 === $categoryId) {
            return null;
        }
        $entity = $this->categoryRepository->find($categoryId);
        if (!$entity instanceof Category) {
            return null;
        }

        return [
            'id' => $entity->getId(),
            'code' => $entity->getCode(),
        ];
    }

    /**
     * Gets the group data for the given identifier.
     */
    private function getGroup(int $groupId): ?array
    {
        if (0 === $groupId) {
            return null;
        }
        $entity = $this->groupRepository->find($groupId);
        if (!$entity instanceof Group) {
            return null;
        }

        return [
            'id' => $entity->getId(),
            'code' => $entity->getCode(),
        ];
    }
}
