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
use App\Repository\TaskRepository;
use App\Tests\Entity\IdTrait;
use App\Tests\Form\Category\CategoryTrait;
use App\Tests\Form\ManagerRegistryTrait;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * @phpstan-require-extends TestCase
 */
trait TaskTrait
{
    use CategoryTrait;
    use IdTrait;
    use ManagerRegistryTrait;

    private ?Task $task = null;

    protected function getTask(): Task
    {
        if (!$this->task instanceof Task) {
            $this->task = new Task();
            $this->task->setName('task');
            $this->getCategory()->addTask($this->task);

            return self::setId($this->task);
        }

        return $this->task;
    }

    protected function getTaskEntityType(): EntityType
    {
        return new EntityType($this->getTaskRegistry());
    }

    protected function getTaskRegistry(): MockObject&ManagerRegistry
    {
        return $this->createManagerRegistry(
            Task::class,
            TaskRepository::class,
            'getSortedBuilder',
            [$this->getTask()]
        );
    }
}
