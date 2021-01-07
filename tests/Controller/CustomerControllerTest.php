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

use App\Entity\Customer;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for {@link App\Controller\CustomerController} class.
 *
 * @author Laurent Muller
 */
class CustomerControllerTest extends AbstractControllerTest
{
    private static ?Customer $entity = null;

    public function getRoutes(): array
    {
        return [
            ['/customer', self::ROLE_USER],
            ['/customer', self::ROLE_ADMIN],
            ['/customer', self::ROLE_SUPER_ADMIN],

            ['/customer/card', self::ROLE_USER],
            ['/customer/card', self::ROLE_ADMIN],
            ['/customer/card', self::ROLE_SUPER_ADMIN],

            ['/customer/add', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/customer/add', self::ROLE_ADMIN],
            ['/customer/add', self::ROLE_SUPER_ADMIN],

            ['/customer/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/customer/edit/1', self::ROLE_ADMIN],
            ['/customer/edit/1', self::ROLE_SUPER_ADMIN],

            ['/customer/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/customer/delete/1', self::ROLE_ADMIN],
            ['/customer/delete/1', self::ROLE_SUPER_ADMIN],

            ['/customer/show/1', self::ROLE_USER],
            ['/customer/show/1', self::ROLE_ADMIN],
            ['/customer/show/1', self::ROLE_SUPER_ADMIN],

            ['/customer/pdf', self::ROLE_USER],
            ['/customer/pdf', self::ROLE_ADMIN],
            ['/customer/pdf', self::ROLE_SUPER_ADMIN],

            ['/customer/excel', self::ROLE_USER],
            ['/customer/excel', self::ROLE_ADMIN],
            ['/customer/excel', self::ROLE_SUPER_ADMIN],
        ];
    }

    protected function addEntities(): void
    {
        if (null === self::$entity) {
            self::$entity = new Customer();
            self::$entity->setCompany('Test Company');
            $this->addEntity(self::$entity);
        }
    }

    protected function deleteEntities(): void
    {
        self::$entity = $this->deleteEntity(self::$entity);
    }
}
