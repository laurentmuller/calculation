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

namespace App\Table;

use App\Entity\Category;
use App\Repository\AbstractCategoryItemRepository;
use App\Repository\CategoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Common abstract table for products and tasks.
 *
 * @author Laurent Muller
 *
 * @template T of \App\Entity\AbstractCategoryItemEntity
 * @template-extends AbstractEntityTable<T>
 */
abstract class AbstractCategoryItemTable extends AbstractEntityTable
{
    /**
     * The category parameter name (int).
     */
    public const PARAM_CATEGORY = 'categoryid';

    /**
     * The category repository.
     */
    protected CategoryRepository $categoryRepository;

    /**
     * Constructor.
     *
     * @psalm-param AbstractCategoryItemRepository<T> $repository
     */
    public function __construct(AbstractCategoryItemRepository $repository, CategoryRepository $categoryRepository)
    {
        parent::__construct($repository);
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function getDataQuery(Request $request): DataQuery
    {
        $query = parent::getDataQuery($request);
        $categoryId = (int) $this->getRequestValue($request, self::PARAM_CATEGORY, 0, false);
        $query->addCustomData(self::PARAM_CATEGORY, $categoryId);

        return $query;
    }

    /**
     * Gets categories.
     */
    protected function getCategories(): array
    {
        return [];
    }

    /**
     * Gets the category for the given identifier.
     */
    protected function getCategory(int $categoryId): ?Category
    {
        return 0 !== $categoryId ? $this->categoryRepository->find($categoryId) : null;
    }

    /**
     * {@inheritDoc}
     */
    protected function search(DataQuery $query, QueryBuilder $builder): void
    {
        parent::search($query, $builder);
        $categoryId = (int) $query->getCustomData(self::PARAM_CATEGORY, 0);
        if (0 !== $categoryId) {
            /** @var string $field */
            $field = $this->repository->getSearchFields('category.id');
            $builder->andWhere($field . '=:' . self::PARAM_CATEGORY)
                ->setParameter(self::PARAM_CATEGORY, $categoryId, Types::INTEGER);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        parent::updateResults($query, $results);
        if (!$query->callback) {
            $categoryId = (int) $query->getCustomData(self::PARAM_CATEGORY, 0);
            $results->addCustomData('category', $this->getCategory($categoryId));
            $results->addCustomData('categories', $this->getCategories());
            $results->addParameter(self::PARAM_CATEGORY, $categoryId);
        }
    }
}
