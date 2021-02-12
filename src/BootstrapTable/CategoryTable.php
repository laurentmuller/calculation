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
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

/**
 * The categories table.
 *
 * @author Laurent Muller
 */
class CategoryTable extends AbstractEntityTable
{
    /**
     * The group parameter name.
     */
    public const PARAM_GROUP = 'groupId';

    /**
     * The selected group identifier.
     */
    private int $groupId = 0;

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
    public function formatProducts(Collection $products, Category $category): string
    {
        return $this->twig->render('table/_cell_table_link.html.twig', [
            'route' => 'table_product',
            'count' => \count($products),
            'title' => 'category.list.product_title',
            'parameters' => [
                ProductTable::PARAM_CATEGORY => $category->getId(),
            ],
        ]);
    }

    /**
     * Formatter for the tasks column.
     */
    public function formatTasks(Collection $tasks, Category $category): string
    {
        return $this->twig->render('table/_cell_table_link.html.twig', [
            'route' => 'table_task',
            'count' => \count($tasks),
            'title' => 'category.list.task_title',
            'parameters' => [
                TaskTable::PARAM_CATEGORY => $category->getId(),
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function addSearch(Request $request, QueryBuilder $builder): string
    {
        $search = parent::addSearch($request, $builder);

        // category?
        $this->groupId = (int) $request->get(self::PARAM_GROUP, 0);
        if (0 !== $this->groupId) {
            $field = $this->repository->getSearchFields('group.id');
            $builder->andWhere($field . '=:' . self::PARAM_GROUP)
                ->setParameter(self::PARAM_GROUP, $this->groupId, Types::INTEGER);
        }

        return $search;
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
     * {@inheritdoc}
     */
    protected function updateParameters(array $parameters): array
    {
        return \array_merge_recursive($parameters, [
            'group' => $this->getGroup(),
            'groups' => $this->getGroups(),
            'params' => [
                self::PARAM_GROUP => $this->groupId,
            ],
        ]);
    }

    /**
     * Gets the selected group or null if none.
     */
    private function getGroup(): ?Group
    {
        if (0 !== $this->groupId) {
            return $this->groupRepository->find($this->groupId);
        }

        return null;
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
