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

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;

/**
 * The products table.
 *
 * @author Laurent Muller
 * @template-extends AbstractCategoryItemTable<\App\Entity\Product>
 */
class ProductTable extends AbstractCategoryItemTable
{
    /**
     * Constructor.
     */
    public function __construct(ProductRepository $repository, CategoryRepository $categoryRepository)
    {
        parent::__construct($repository, $categoryRepository);
    }

    /**
     * {@inheritDoc}
     */
    protected function getCategories(): array
    {
        return $this->categoryRepository->getProductListCount();
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
}
