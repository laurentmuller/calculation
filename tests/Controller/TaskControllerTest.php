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

namespace App\Tests\Controller;

use App\Controller\TaskController;
use App\Tests\EntityTrait\CategoryTrait;
use App\Tests\EntityTrait\GroupTrait;
use App\Tests\EntityTrait\TaskItemTrait;
use App\Tests\EntityTrait\TaskTrait;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(TaskController::class)]
class TaskControllerTest extends AbstractControllerTestCase
{
    use CategoryTrait;
    use GroupTrait;
    use TaskItemTrait;
    use TaskTrait;

    public static function getRoutes(): array
    {
        return [
            ['/task', self::ROLE_USER],
            ['/task', self::ROLE_ADMIN],
            ['/task', self::ROLE_SUPER_ADMIN],

            ['/task/add', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/task/add', self::ROLE_ADMIN],
            ['/task/add', self::ROLE_SUPER_ADMIN],

            ['/task/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/task/edit/1', self::ROLE_ADMIN],
            ['/task/edit/1', self::ROLE_SUPER_ADMIN],

            ['/task/clone/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/task/clone/1', self::ROLE_ADMIN],
            ['/task/clone/1', self::ROLE_SUPER_ADMIN],

            ['/task/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/task/delete/1', self::ROLE_ADMIN],
            ['/task/delete/1', self::ROLE_SUPER_ADMIN],

            ['/task/clone/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/task/clone/1', self::ROLE_ADMIN],
            ['/task/clone/1', self::ROLE_SUPER_ADMIN],

            ['/task/show/1', self::ROLE_USER],
            ['/task/show/1', self::ROLE_ADMIN],
            ['/task/show/1', self::ROLE_SUPER_ADMIN],

            ['/task/pdf', self::ROLE_USER],
            ['/task/pdf', self::ROLE_ADMIN],
            ['/task/pdf', self::ROLE_SUPER_ADMIN],

            ['/task/excel', self::ROLE_USER],
            ['/task/excel', self::ROLE_ADMIN],
            ['/task/excel', self::ROLE_SUPER_ADMIN],

            ['/task/compute/1', self::ROLE_USER],
            ['/task/compute/1', self::ROLE_ADMIN],
            ['/task/compute/1', self::ROLE_SUPER_ADMIN],
        ];
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function addEntities(): void
    {
        $group = $this->getGroup();
        $category = $this->getCategory($group);
        $task = $this->getTask($category);
        $this->getTaskItem($task);
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteEntities(): void
    {
        $this->deleteTaskItem();
        $this->deleteTask();
        $this->deleteCategory();
        $this->deleteGroup();
    }
}
