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
use App\Repository\CategoryRepository;
use App\Repository\GroupRepository;
use App\Service\IndexService;
use App\Traits\AuthorizationCheckerAwareTrait;
use App\Traits\TableCellTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Filesystem\Path;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;

/**
 * The category table.
 *
 * @extends AbstractEntityTable<Category, CategoryRepository>
 */
class CategoryTable extends AbstractEntityTable implements ServiceSubscriberInterface
{
    use AuthorizationCheckerAwareTrait;
    use ServiceMethodsSubscriberTrait;
    use TableCellTrait;

    /**
     * The group parameter name (int).
     */
    public const string PARAM_GROUP = 'groupId';

    public function __construct(
        CategoryRepository $repository,
        protected readonly Environment $twig,
        private readonly GroupRepository $groupRepository,
        private readonly IndexService $indexService
    ) {
        parent::__construct($repository);
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
            'category.list.product_title',
            $route,
            AbstractCategoryItemTable::PARAM_CATEGORY
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
            'category.list.task_title',
            $route,
            AbstractCategoryItemTable::PARAM_CATEGORY
        );
    }

    #[\Override]
    protected function addSearch(DataQuery $query, QueryBuilder $builder, string $alias): bool
    {
        $result = parent::addSearch($query, $builder, $alias);
        $groupId = $this->getQueryGroupId($query);
        if (0 === $groupId) {
            return $result;
        }
        /** @phpstan-var string $field */
        $field = $this->getRepository()->getSearchFields('group.id', $alias);
        $builder->andWhere($field . '=:' . self::PARAM_GROUP)
            ->setParameter(self::PARAM_GROUP, $groupId, Types::INTEGER);

        return true;
    }

    #[\Override]
    protected function count(): int
    {
        return $this->indexService->getCatalog()['category'];
    }

    #[\Override]
    protected function createQueryBuilder(string $alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->getRepository()->getTableQueryBuilder($alias);
    }

    #[\Override]
    protected function getColumnDefinitions(): string
    {
        return Path::join(__DIR__, 'Definition', 'category.json');
    }

    #[\Override]
    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        parent::updateResults($query, $results);
        if ($query->callback) {
            return;
        }
        $groupId = $this->getQueryGroupId($query);
        $results->addParameter(self::PARAM_GROUP, $groupId);
        $results->addCustomData('group', $this->getGroup($groupId));
        $results->addCustomData('dropdown', $this->getDropDownValues());
    }

    /**
     * Gets drop-down values.
     */
    private function getDropDownValues(): array
    {
        return $this->groupRepository->getDropDown();
    }

    /**
     * Gets the group for the given identifier.
     */
    private function getGroup(int $groupId): ?Group
    {
        return 0 !== $groupId ? $this->groupRepository->find($groupId) : null;
    }

    private function getQueryGroupId(DataQuery $query): int
    {
        return $query->getIntParameter(self::PARAM_GROUP);
    }
}
