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
class ProductTable extends AbstractBootstrapEntityTable
{
    /**
     * The category parameter name.
     */
    private const PARAM_CATEGORY = 'categoryId';

    /**
     * The selected category identifier.
     */
    private int $categoryId = 0;

    /**
     * Constructor.
     */
    public function __construct(ProductRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Gets the selected category or null if none.
     */
    public function getCategory(CategoryRepository $repository): ?Category
    {
        if (0 !== $this->categoryId) {
            return $repository->find($this->categoryId);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function addSearch(Request $request, QueryBuilder $builder): string
    {
        $search = parent::addSearch($request, $builder);

        // category?
        $this->categoryId = (int) $request->get(self::PARAM_CATEGORY, 0);
        if (0 !== $this->categoryId) {
            $field = $this->repository->getSearchFields('category.id');
            $builder->andWhere($field . '=:' . self::PARAM_CATEGORY)
                ->setParameter(self::PARAM_CATEGORY, $this->categoryId, Types::INTEGER);
        }

        return $search;
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        $path = __DIR__ . '/Definition/product.json';

        return $this->deserializeColumns($path);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['description' => BootstrapColumn::SORT_ASC];
    }
}
