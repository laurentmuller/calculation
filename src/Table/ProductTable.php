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
use App\Service\IndexService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Filesystem\Path;

/**
 * The product table.
 *
 * @extends AbstractCategoryItemTable<Product, ProductRepository>
 */
class ProductTable extends AbstractCategoryItemTable
{
    public function __construct(
        ProductRepository $repository,
        CategoryRepository $categoryRepository,
        GroupRepository $groupRepository,
        private readonly IndexService $indexService
    ) {
        parent::__construct($repository, $categoryRepository, $groupRepository);
    }

    #[\Override]
    protected function count(): int
    {
        return $this->indexService->getCatalog()['product'];
    }

    #[\Override]
    protected function createQueryBuilder(string $alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->getRepository()->getTableQueryBuilder($alias);
    }

    #[\Override]
    protected function getColumnDefinitions(): string
    {
        return Path::join(__DIR__, 'Definition', 'product.json');
    }

    #[\Override]
    protected function getDropDownValues(): array
    {
        return $this->categoryRepository->getDropDownProducts();
    }
}
