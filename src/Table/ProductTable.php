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

use App\Entity\Product;
use App\Repository\AbstractRepository;
use App\Repository\CategoryRepository;
use App\Repository\GroupRepository;
use App\Repository\ProductRepository;
use App\Utils\FileUtils;
use Doctrine\ORM\QueryBuilder;

/**
 * The product table.
 *
 * @template-extends AbstractCategoryItemTable<Product, ProductRepository>
 */
class ProductTable extends AbstractCategoryItemTable
{
    public function __construct(
        ProductRepository $repository,
        CategoryRepository $categoryRepository,
        GroupRepository $groupRepository
    ) {
        parent::__construct($repository, $categoryRepository, $groupRepository);
    }

    protected function createQueryBuilder(string $alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->getRepository()->getTableQueryBuilder($alias);
    }

    protected function getColumnDefinitions(): string
    {
        return FileUtils::buildPath(__DIR__, 'Definition', 'product.json');
    }

    protected function getDefaultOrder(): array
    {
        return ['description' => self::SORT_ASC];
    }

    protected function getDropDownValues(): array
    {
        return $this->categoryRepository->getDropDownProducts();
    }
}
