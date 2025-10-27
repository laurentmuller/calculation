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

use App\Entity\TaskItem;
use App\Form\Task\TaskItemType;
use App\Tests\Form\EntityTypeTestCase;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @extends EntityTypeTestCase<TaskItem, TaskItemType>
 */
final class TaskItemTypeTest extends EntityTypeTestCase
{
    #[\Override]
    protected function getData(): array
    {
        return [
            'name' => 'name',
            'position' => 0,
            'margins' => new ArrayCollection(),
        ];
    }

    #[\Override]
    protected function getEntityClass(): string
    {
        return TaskItem::class;
    }

    #[\Override]
    protected function getFormTypeClass(): string
    {
        return TaskItemType::class;
    }
}
