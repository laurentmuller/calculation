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
use App\Repository\TaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * The tasks table.
 *
 * @author Laurent Muller
 */
class TaskTable extends AbstractEntityTable
{
    /**
     * The category parameter name.
     */
    public const PARAM_CATEGORY = 'categoryId';

    /**
     * The selected category identifier.
     */
    private int $categoryId = 0;

    /**
     * The category repository.
     */
    private CategoryRepository $categoryRepository;

    /**
     * Constructor.
     */
    public function __construct(TaskRepository $repository, CategoryRepository $categoryRepository)
    {
        parent::__construct($repository);
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Gets the selected category identifier.
     */
    public function getCategoryId(): int
    {
        return $this->categoryId;
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
    protected function getColumnDefinitions(): string
    {
        return __DIR__ . '/Definition/task.json';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['name' => Column::SORT_ASC];
    }

    /**
     * {@inheritdoc}
     */
    protected function updateParameters(array $parameters): array
    {
        return \array_merge_recursive($parameters, [
            'category' => $this->getCategory(),
            'categories' => $this->getCategories(),
            'params' => [
                self::PARAM_CATEGORY => $this->categoryId,
             ],
        ]);
    }

    /**
     * Gets categories.
     *
     * @return Category[]
     */
    private function getCategories(): array
    {
        return $this->categoryRepository->getListTaskCount();
    }

    /**
     * Gets the selected category or null if none.
     */
    private function getCategory(): ?Category
    {
        if (0 !== $this->categoryId) {
            return $this->categoryRepository->find($this->categoryId);
        }

        return null;
    }
}
