<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Category;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for {@link App\Controller\GroupController} class.
 *
 * @author Laurent Muller
 */
class GroupControllerTest extends AbstractControllerTest
{
    private static ?Category $entity = null;

    public function getRoutes(): array
    {
        return [
            ['/group', self::ROLE_USER],
            ['/group', self::ROLE_ADMIN],
            ['/group', self::ROLE_SUPER_ADMIN],

            ['/group/table', self::ROLE_USER],
            ['/group/table', self::ROLE_ADMIN],
            ['/group/table', self::ROLE_SUPER_ADMIN],

            ['/group/add', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/group/add', self::ROLE_ADMIN],
            ['/group/add', self::ROLE_SUPER_ADMIN],

            ['/group/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/group/edit/1', self::ROLE_ADMIN],
            ['/group/edit/1', self::ROLE_SUPER_ADMIN],

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
            self::$entity = new Category();
            self::$entity->setCode('Test Code');
            $this->addEntity(self::$entity);
        }
    }

    protected function deleteEntities(): void
    {
        self::$entity = $this->deleteEntity(self::$entity);
    }
}
