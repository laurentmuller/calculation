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

use App\Entity\TaskItemMargin;
use App\Form\Task\TaskItemMarginType;
use App\Tests\Form\EntityTypeTestCase;

/**
 * @extends EntityTypeTestCase<TaskItemMargin, TaskItemMarginType>
 */
class TaskItemMarginTypeTest extends EntityTypeTestCase
{
    #[\Override]
    protected function getData(): array
    {
        return [
            'minimum' => 0.0,
            'maximum' => 1.1,
            'value' => 0.0,
        ];
    }

    #[\Override]
    protected function getEntityClass(): string
    {
        return TaskItemMargin::class;
    }

    #[\Override]
    protected function getFormTypeClass(): string
    {
        return TaskItemMarginType::class;
    }
}
