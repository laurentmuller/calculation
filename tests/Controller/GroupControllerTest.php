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

use App\Entity\Group;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for {@link App\Controller\GroupController} class.
 *
 * @author Laurent Muller
 */
class GroupControllerTest extends AbstractControllerTest
{
    private static ?Group $entity = null;

    public function getRoutes(): array
    {
        return [
            ['/group', self::ROLE_USER],
            ['/group', self::ROLE_ADMIN],
            ['/group', self::ROLE_SUPER_ADMIN],

            ['/group/add', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/group/add', self::ROLE_ADMIN],
            ['/group/add', self::ROLE_SUPER_ADMIN],

            ['/group/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/group/edit/1', self::ROLE_ADMIN],
            ['/group/edit/1', self::ROLE_SUPER_ADMIN],

            ['/group/clone/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/group/clone/1', self::ROLE_ADMIN],
            ['/group/clone/1', self::ROLE_SUPER_ADMIN],

            ['/group/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/group/delete/1', self::ROLE_ADMIN],
            ['/group/delete/1', self::ROLE_SUPER_ADMIN],

            ['/group/show/1', self::ROLE_USER],
            ['/group/show/1', self::ROLE_ADMIN],
            ['/group/show/1', self::ROLE_SUPER_ADMIN],

            ['/group/pdf', self::ROLE_USER],
            ['/group/pdf', self::ROLE_ADMIN],
            ['/group/pdf', self::ROLE_SUPER_ADMIN],

            ['/group/excel', self::ROLE_USER],
            ['/group/excel', self::ROLE_ADMIN],
            ['/group/excel', self::ROLE_SUPER_ADMIN],
        ];
    }

    protected function addEntities(): void
    {
        if (null === self::$entity) {
            self::$entity = new Group();
            self::$entity->setCode('Test Code');
            $this->addEntity(self::$entity);
        }
    }

    protected function deleteEntities(): void
    {
        self::$entity = $this->deleteEntity(self::$entity);
    }
}
