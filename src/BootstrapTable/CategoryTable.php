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
use App\Entity\Group;
use App\Repository\CategoryRepository;
use App\Repository\GroupRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

/**
 * The categories table.
 *
 * @author Laurent Muller
 * @template-extends AbstractEntityTable<\App\Entity\Category>
 */
class CategoryTable extends AbstractEntityTable
{
    /**
     * The group parameter name (int).
     */
    public const PARAM_GROUP = 'groupId';

    /**
     * The group repository.
     */
    private GroupRepository $groupRepository;

    /**
     * The template renderer.
     */
    private Environment $twig;

    /**
     * Constructor.
     */
    public function __construct(CategoryRepository $repository, GroupRepository $groupRepository, Environment $twig)
    {
        parent::__construct($repository);
        $this->groupRepository = $groupRepository;
        $this->twig = $twig;
    }

    /**
     * Formatter for the products column.
     */
    public function formatProducts(\Countable $products, Category $category): string
    {
        return $this->twig->render('table/_cell_table_link.html.twig', [
            'route' => 'table_product',
            'count' => $products->count(),
            'title' => 'category.list.product_title',
            'parameters' => [
                ProductTable::PARAM_CATEGORY => $category->getId(),
            ],
        ]);
    }

    /**
     * Formatter for the tasks column.
     */
    public function formatTasks(\Countable $tasks, Category $category): string
    {
        return $this->twig->render('table/_cell_table_link.html.twig', [
            'route' => 'table_task',
            'count' => $tasks->count(),
            'title' => 'category.list.task_title',
            'parameters' => [
                TaskTable::PARAM_CATEGORY => $category->getId(),
            ],
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getDataQuery(Request $request): DataQuery
    {
        $query = parent::getDataQuery($request);
        $groupId = (int) $request->get(self::PARAM_GROUP, 0);
        $query->addCustomData(self::PARAM_GROUP, $groupId);

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnDefinitions(): string
    {
        return __DIR__ . '/Definition/category.json';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['code' => Column::SORT_ASC];
    }

    /**
     * {@inheritDoc}
     */
    protected function search(DataQuery $query, QueryBuilder $builder): void
    {
        parent::search($query, $builder);
        if (0 !== $groupId = (int) $query->getCustomData(self::PARAM_GROUP, 0)) {
            $field = \strval($this->repository->getSearchFields('group.id'));
            $builder->andWhere($field . '=:' . self::PARAM_GROUP)
                ->setParameter(self::PARAM_GROUP, $groupId, Types::INTEGER);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        parent::updateResults($query, $results);
        if (!$query->callback) {
            $groupId = $query->getCustomData(self::PARAM_GROUP, 0);
            $results->addCustomData('group', $this->getGroup($groupId));
            $results->addCustomData('groups', $this->getGroups());
            $results->addParameter(self::PARAM_GROUP, $groupId);
        }
    }

    /**
     * Gets the group for the given identifier.
     */
    private function getGroup(int $groupId): ?Group
    {
        return 0 !== $groupId ? $this->groupRepository->find($groupId) : null;
    }

    /**
     * Gets groups.
     *
     * @return Group[]
     */
    private function getGroups(): array
    {
        return $this->groupRepository->getListCount();
    }
}
