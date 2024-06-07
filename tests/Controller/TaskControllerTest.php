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

use App\Controller\AbstractController;
use App\Controller\AbstractEntityController;
use App\Controller\TaskController;
use App\Tests\EntityTrait\TaskItemTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(AbstractController::class)]
#[CoversClass(AbstractEntityController::class)]
#[CoversClass(TaskController::class)]
class TaskControllerTest extends AbstractControllerTestCase
{
    use TaskItemTrait;

    public static function getRoutes(): \Iterator
    {
        yield ['/task', self::ROLE_USER];
        yield ['/task', self::ROLE_ADMIN];
        yield ['/task', self::ROLE_SUPER_ADMIN];
        yield ['/task/add', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/task/add', self::ROLE_ADMIN];
        yield ['/task/add', self::ROLE_SUPER_ADMIN];
        yield ['/task/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/task/edit/1', self::ROLE_ADMIN];
        yield ['/task/edit/1', self::ROLE_SUPER_ADMIN];
        yield ['/task/clone/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/task/clone/1', self::ROLE_ADMIN];
        yield ['/task/clone/1', self::ROLE_SUPER_ADMIN];
        yield ['/task/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/task/delete/1', self::ROLE_ADMIN];
        yield ['/task/delete/1', self::ROLE_SUPER_ADMIN];
        yield ['/task/clone/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/task/clone/1', self::ROLE_ADMIN];
        yield ['/task/clone/1', self::ROLE_SUPER_ADMIN];
        yield ['/task/show/1', self::ROLE_USER];
        yield ['/task/show/1', self::ROLE_ADMIN];
        yield ['/task/show/1', self::ROLE_SUPER_ADMIN];
        yield ['/task/pdf', self::ROLE_USER];
        yield ['/task/pdf', self::ROLE_ADMIN];
        yield ['/task/pdf', self::ROLE_SUPER_ADMIN];
        yield ['/task/excel', self::ROLE_USER];
        yield ['/task/excel', self::ROLE_ADMIN];
        yield ['/task/excel', self::ROLE_SUPER_ADMIN];
        yield ['/task/compute/1', self::ROLE_USER];
        yield ['/task/compute/1', self::ROLE_ADMIN];
        yield ['/task/compute/1', self::ROLE_SUPER_ADMIN];
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function addEntities(): void
    {
        $task = $this->getTask();
        $this->getTaskItem($task);
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteEntities(): void
    {
        $this->deleteTaskItem();
    }
}
