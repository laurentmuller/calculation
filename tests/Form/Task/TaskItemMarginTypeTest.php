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
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @extends EntityTypeTestCase<TaskItemMargin, TaskItemMarginType>
 */
#[CoversClass(TaskItemMarginType::class)]
class TaskItemMarginTypeTest extends EntityTypeTestCase
{
    protected function getData(): array
    {
        return [
            'minimum' => 0.0,
            'maximum' => 1.1,
            'value' => 0.0,
        ];
    }

    protected function getEntityClass(): string
    {
        return TaskItemMargin::class;
    }

    protected function getFormTypeClass(): string
    {
        return TaskItemMarginType::class;
    }
}