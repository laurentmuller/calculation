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
use App\Tests\Form\CategoryTrait;
use App\Tests\Form\EntityTypeTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Form\PreloadedExtension;

/**
 * @extends EntityTypeTestCase<Task, TaskType>
 */
#[CoversClass(TaskType::class)]
class TaskTypeTest extends EntityTypeTestCase
{
    use CategoryTrait;

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

    protected function getEntityClass(): string
    {
        return Task::class;
    }

    /**
     * @throws Exception|\ReflectionException
     */
    protected function getExtensions(): array
    {
        /** @psalm-var array $extensions */
        $extensions = parent::getExtensions();
        $types = [
            $this->getEntityType(),
        ];
        $extensions[] = new PreloadedExtension($types, []);

        return $extensions;
    }

    protected function getFormTypeClass(): string
    {
        return TaskType::class;
    }
}
