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

use App\Entity\Category;
use App\Entity\Group;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for {@link CategoryController} class.
 */
class CategoryControllerTest extends AbstractControllerTest
{
    private static ?Category $entity = null;
    private static ?Group $group = null;

    public function getRoutes(): array
    {
        return [
            ['/category', self::ROLE_USER],
            ['/category', self::ROLE_ADMIN],
            ['/category', self::ROLE_SUPER_ADMIN],

            ['/category/add', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/category/add', self::ROLE_ADMIN],
            ['/category/add', self::ROLE_SUPER_ADMIN],

            ['/category/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/category/edit/1', self::ROLE_ADMIN],
            ['/category/edit/1', self::ROLE_SUPER_ADMIN],

            ['/category/clone/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/category/clone/1', self::ROLE_ADMIN],
            ['/category/clone/1', self::ROLE_SUPER_ADMIN],

            ['/category/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/category/delete/1', self::ROLE_ADMIN],
            ['/category/delete/1', self::ROLE_SUPER_ADMIN],

            ['/category/show/1', self::ROLE_USER],
            ['/category/show/1', self::ROLE_ADMIN],
            ['/category/show/1', self::ROLE_SUPER_ADMIN],

            ['/category/pdf', self::ROLE_USER],
            ['/category/pdf', self::ROLE_ADMIN],
            ['/category/pdf', self::ROLE_SUPER_ADMIN],

            ['/category/excel', self::ROLE_USER],
            ['/category/excel', self::ROLE_ADMIN],
            ['/category/excel', self::ROLE_SUPER_ADMIN],
        ];
    }

    protected function addEntities(): void
    {
        if (null === self::$group) {
            self::$group = new Group();
            self::$group->setCode('Test Parent');
            $this->addEntity(self::$group);
        }
        if (null === self::$entity) {
            self::$entity = new Category();
            self::$entity->setCode('Test Code')
                ->setGroup(self::$group);
            $this->addEntity(self::$entity);
        }
    }

    protected function deleteEntities(): void
    {
        self::$entity = $this->deleteEntity(self::$entity);
        self::$group = $this->deleteEntity(self::$group);
    }
}
