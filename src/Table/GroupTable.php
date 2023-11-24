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
use App\Traits\AuthorizationCheckerAwareTrait;
use App\Traits\TableCellTrait;
use App\Utils\FileUtils;
use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;
use Twig\Environment;

/**
 * The groups table.
 *
 * @method GroupRepository getRepository()
 *
 * @template-extends AbstractEntityTable<Group>
 */
class GroupTable extends AbstractEntityTable implements ServiceSubscriberInterface
{
    use AuthorizationCheckerAwareTrait;
    use ServiceSubscriberTrait;
    use TableCellTrait;

    public function __construct(// phpcs:ignore
        GroupRepository $repository,
        protected readonly Environment $twig
    ) {
        parent::__construct($repository);
    }

    /**
     * Formatter for the category column.
     *
     * @throws \Twig\Error\Error
     *
     * @psalm-param array{id: int} $entity
     *
     * @psalm-api
     */
    public function formatCategories(int $value, array $entity): string
    {
        $route = $this->isGrantedList(Category::class) ? 'category_table' : false;

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
     * @throws \Twig\Error\Error
     *
     * @psalm-param array{id: int} $entity
     *
     * @psalm-api
     */
    public function formatProducts(int $value, array $entity): string
    {
        $route = $this->isGrantedList(Product::class) ? 'product_table' : false;

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
     * @throws \Twig\Error\Error
     *
     * @psalm-param array{id: int} $entity
     *
     * @psalm-api
     */
    public function formatTasks(int $value, array $entity): string
    {
        $route = $this->isGrantedList(Task::class) ? 'task_table' : false;

        return $this->renderCell(
            $value,
            $entity,
            'group.list.task_title',
            $route,
            CategoryTable::PARAM_GROUP
        );
    }

    protected function createDefaultQueryBuilder(string $alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->getRepository()->getTableQueryBuilder($alias);
    }

    protected function getColumnDefinitions(): string
    {
        return FileUtils::buildPath(__DIR__, 'Definition', 'group.json');
    }

    protected function getDefaultOrder(): array
    {
        return ['code' => self::SORT_ASC];
    }
}
