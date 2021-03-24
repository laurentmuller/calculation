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

namespace App\BootstrapTable;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * The products table.
 *
 * @author Laurent Muller
 */
class ProductTable extends AbstractEntityTable
{
    /**
     * The category parameter name (int).
     */
    public const PARAM_CATEGORY = 'categoryId';

    /**
     * The category repository.
     */
    private CategoryRepository $categoryRepository;

    /**
     * Constructor.
     */
    public function __construct(ProductRepository $repository, CategoryRepository $categoryRepository)
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
        $categoryId = (int) $request->get(self::PARAM_CATEGORY, 0);
        $query->addCustomData(self::PARAM_CATEGORY, $categoryId);

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnDefinitions(): string
    {
        return __DIR__ . '/Definition/product.json';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['description' => Column::SORT_ASC];
    }

    /**
     * {@inheritDoc}
     */
    protected function search(DataQuery $query, QueryBuilder $builder): void
    {
        parent::search($query, $builder);
        if (0 !== $categoryId = $query->getCustomData(self::PARAM_CATEGORY, 0)) {
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
        $categoryId = $query->getCustomData(self::PARAM_CATEGORY, 0);
        $results->addCustomData('category', $this->getCategory($categoryId));
        $results->addCustomData('categories', $this->getCategories());
        $results->addParameter(self::PARAM_CATEGORY, $categoryId);
    }

    /**
     * Gets categories.
     *
     * @return Category[]
     */
    private function getCategories(): array
    {
        return $this->categoryRepository->getListCount();
    }

    /**
     * Gets the category for the given identifier.
     */
    private function getCategory(int $categoryId): ?Category
    {
        if (0 !== $categoryId) {
            return $this->categoryRepository->find($categoryId);
        }

        return null;
    }
}
