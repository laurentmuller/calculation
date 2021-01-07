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

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Group;
use App\Entity\Task;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for {@link App\Controller\TaskController} class.
 *
 * @author Laurent Muller
 */
class TaskControllerTest extends AbstractControllerTest
{
    private static ?Category $category = null;
    private static ?Task $entity = null;
    private static ?Group $group = null;

    public function getRoutes(): array
    {
        return [
            ['/task', self::ROLE_USER],
            ['/task', self::ROLE_ADMIN],
            ['/task', self::ROLE_SUPER_ADMIN],

            ['/task/card', self::ROLE_USER],
            ['/task/card', self::ROLE_ADMIN],
            ['/task/card', self::ROLE_SUPER_ADMIN],

            ['/task/add', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/task/add', self::ROLE_ADMIN],
            ['/task/add', self::ROLE_SUPER_ADMIN],

            ['/task/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/task/edit/1', self::ROLE_ADMIN],
            ['/task/edit/1', self::ROLE_SUPER_ADMIN],

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
        ];
    }

    protected function addEntities(): void
    {
        if (null === self::$group) {
            self::$group = new Group();
            self::$group->setCode('Test Group');
            $this->addEntity(self::$group);
        }
        if (null === self::$category) {
            self::$category = new Category();
            self::$category->setCode('Test Category')
                ->setGroup(self::$group);
            $this->addEntity(self::$category);
        }
        if (null === self::$entity) {
            self::$entity = new Task();
            self::$entity->setName('Test Task')
                ->setCategory(self::$category);
            $this->addEntity(self::$entity);
        }
    }

    protected function deleteEntities(): void
    {
        self::$entity = $this->deleteEntity(self::$entity);
        self::$category = $this->deleteEntity(self::$category);
        self::$group = $this->deleteEntity(self::$group);
    }
}
