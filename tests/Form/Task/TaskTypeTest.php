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

namespace App\Tests\Form\Task;

use App\Entity\Task;
use App\Form\Task\TaskType;
use App\Tests\Form\Category\CategoryTrait;
use App\Tests\Form\EntityTypeTestCase;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @extends EntityTypeTestCase<Task, TaskType>
 */
class TaskTypeTest extends EntityTypeTestCase
{
    use CategoryTrait;

    #[\Override]
    protected function getData(): array
    {
        return [
            'name' => 'name',
            'category' => null,
            'unit' => 'unit',
            'supplier' => 'supplier',
            'items' => new ArrayCollection(),
        ];
    }

    #[\Override]
    protected function getEntityClass(): string
    {
        return Task::class;
    }

    #[\Override]
    protected function getFormTypeClass(): string
    {
        return TaskType::class;
    }

    /**
     * @throws \ReflectionException
     */
    #[\Override]
    protected function getPreloadedExtensions(): array
    {
        return [
            $this->getCategoryEntityType(),
        ];
    }
}
