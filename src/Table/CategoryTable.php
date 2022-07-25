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
use App\Repository\CategoryRepository;
use App\Repository\GroupRepository;
use App\Traits\CheckerAwareTrait;
use App\Util\FileUtils;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;
use Twig\Environment;

/**
 * The categories table.
 *
 * @template-extends AbstractEntityTable<Category>
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CategoryTable extends AbstractEntityTable implements ServiceSubscriberInterface
{
    use CheckerAwareTrait;
    use ServiceSubscriberTrait;

    /**
     * The group parameter name (int).
     */
    final public const PARAM_GROUP = 'groupid';

    /**
     * Constructor.
     */
    public function __construct(
        CategoryRepository $repository,
        private readonly GroupRepository $groupRepository,
        private readonly Environment $twig
    ) {
        parent::__construct($repository);
    }

    /**
     * Formatter for the product column.
     *
     * @throws \Twig\Error\Error
     */
    public function formatProducts(\Countable $products, Category $category): string
    {
        $context = [
            'count' => $products->count(),
            'title' => 'category.list.product_title',
            'route' => $this->isGrantedList(Product::class) ? 'product_table' : false,
            'parameters' => [
                AbstractCategoryItemTable::PARAM_CATEGORY => $category->getId(),
            ],
        ];

        return $this->twig->render('macros/_cell_table_link.html.twig', $context);
    }

    /**
     * Formatter for the task column.
     *
     * @throws \Twig\Error\Error
     */
    public function formatTasks(\Countable $tasks, Category $category): string
    {
        $context = [
            'count' => $tasks->count(),
            'title' => 'category.list.task_title',
            'route' => $this->isGrantedList(Task::class) ? 'task_table' : false,
            'parameters' => [
                AbstractCategoryItemTable::PARAM_CATEGORY => $category->getId(),
            ],
        ];

        return $this->twig->render('macros/_cell_table_link.html.twig', $context);
    }

    /**
     * {@inheritDoc}
     */
    public function getDataQuery(Request $request): DataQuery
    {
        $query = parent::getDataQuery($request);
        $groupId = $this->getRequestInt($request, self::PARAM_GROUP);
        $query->addCustomData(self::PARAM_GROUP, $groupId);

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnDefinitions(): string
    {
        return FileUtils::buildPath(__DIR__, 'Definition', 'category.json');
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['code' => self::SORT_ASC];
    }

    /**
     * {@inheritDoc}
     */
    protected function search(DataQuery $query, QueryBuilder $builder): void
    {
        parent::search($query, $builder);
        if (0 !== $groupId = (int) $query->getCustomData(self::PARAM_GROUP, 0)) {
            /** @var string $field */
            $field = $this->repository->getSearchFields('group.id');
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
            $groupId = (int) $query->getCustomData(self::PARAM_GROUP, 0);
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
     * Gets groupes.
     */
    private function getGroups(): array
    {
        return $this->groupRepository->getListCountCategories();
    }
}
