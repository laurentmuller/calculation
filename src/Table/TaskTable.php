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

namespace App\Table;

use App\Repository\CategoryRepository;
use App\Repository\TaskRepository;
use App\Util\FileUtils;

/**
 * The tasks table.
 *
 * @author Laurent Muller
 * @template-extends AbstractCategoryItemTable<\App\Entity\Task>
 */
class TaskTable extends AbstractCategoryItemTable
{
    /**
     * Constructor.
     */
    public function __construct(TaskRepository $repository, CategoryRepository $categoryRepository)
    {
        parent::__construct($repository, $categoryRepository);
    }

    /**
     * {@inheritDoc}
     */
    protected function getCategories(): array
    {
        return $this->categoryRepository->getDropDownTasks();
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnDefinitions(): string
    {
        return FileUtils::buildPath(__DIR__, 'Definition', 'task.json');
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['name' => self::SORT_ASC];
    }
}
