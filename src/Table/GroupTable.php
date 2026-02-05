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
use App\Entity\Product;
use App\Entity\Task;
use App\Repository\AbstractRepository;
use App\Repository\GroupRepository;
use App\Service\IndexService;
use App\Traits\AuthorizationCheckerAwareTrait;
use App\Traits\TableCellTrait;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Filesystem\Path;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;

/**
 * The group table.
 *
 * @extends AbstractEntityTable<Group, GroupRepository>
 */
class GroupTable extends AbstractEntityTable implements ServiceSubscriberInterface
{
    use AuthorizationCheckerAwareTrait;
    use ServiceMethodsSubscriberTrait;
    use TableCellTrait;

    public function __construct(
        GroupRepository $repository,
        protected readonly Environment $twig,
        private readonly IndexService $indexService
    ) {
        parent::__construct($repository);
    }

    /**
     * Formatter for the category column.
     *
     * @phpstan-param array{id: int} $entity
     *
     * @throws \Twig\Error\Error
     */
    public function formatCategories(int $value, array $entity): string
    {
        $route = $this->isGrantedList(Category::class) ? 'category_index' : false;

        return $this->renderCell(
            $value,
            $entity,
            'group.list.category_title',
            $route,
            CategoryTable::PARAM_GROUP
        );
    }

    /**
     * Formatter for the product column.
     *
     * @phpstan-param array{id: int} $entity
     *
     * @throws \Twig\Error\Error
     */
    public function formatProducts(int $value, array $entity): string
    {
        $route = $this->isGrantedList(Product::class) ? 'product_index' : false;

        return $this->renderCell(
            $value,
            $entity,
            'group.list.product_title',
            $route,
            CategoryTable::PARAM_GROUP
        );
    }

    /**
     * Formatter for the task column.
     *
     * @phpstan-param array{id: int} $entity
     *
     * @throws \Twig\Error\Error
     */
    public function formatTasks(int $value, array $entity): string
    {
        $route = $this->isGrantedList(Task::class) ? 'task_index' : false;

        return $this->renderCell(
            $value,
            $entity,
            'group.list.task_title',
            $route,
            CategoryTable::PARAM_GROUP
        );
    }

    #[\Override]
    protected function count(): int
    {
        return $this->indexService->getCatalog()['group'];
    }

    #[\Override]
    protected function createQueryBuilder(string $alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->getRepository()->getTableQueryBuilder($alias);
    }

    #[\Override]
    protected function getColumnDefinitions(): string
    {
        return Path::join(__DIR__, 'Definition', 'group.json');
    }
}
