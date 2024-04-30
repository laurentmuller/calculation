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

use App\Entity\Task;
use App\Repository\AbstractRepository;
use App\Repository\CategoryRepository;
use App\Repository\GroupRepository;
use App\Repository\TaskRepository;
use App\Utils\FileUtils;
use Doctrine\ORM\QueryBuilder;

/**
 * The task table.
 *
 * @template-extends AbstractCategoryItemTable<Task, TaskRepository>
 */
class TaskTable extends AbstractCategoryItemTable
{
    public function __construct(
        TaskRepository $repository,
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
        return FileUtils::buildPath(__DIR__, 'Definition', 'task.json');
    }

    protected function getDefaultOrder(): array
    {
        return ['name' => self::SORT_ASC];
    }

    protected function getDropDownValues(): array
    {
        return $this->categoryRepository->getDropDownTasks();
    }
}
