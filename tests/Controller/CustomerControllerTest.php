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

use App\Controller\CustomerController;
use App\Entity\Customer;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(CustomerController::class)]
class CustomerControllerTest extends AbstractControllerTestCase
{
    private ?Customer $entity = null;

    public static function getRoutes(): \Iterator
    {
        yield ['/customer', self::ROLE_USER];
        yield ['/customer', self::ROLE_ADMIN];
        yield ['/customer', self::ROLE_SUPER_ADMIN];
        yield ['/customer/add', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/customer/add', self::ROLE_ADMIN];
        yield ['/customer/add', self::ROLE_SUPER_ADMIN];
        yield ['/customer/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/customer/edit/1', self::ROLE_ADMIN];
        yield ['/customer/edit/1', self::ROLE_SUPER_ADMIN];
        yield ['/customer/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/customer/delete/1', self::ROLE_ADMIN];
        yield ['/customer/delete/1', self::ROLE_SUPER_ADMIN];
        yield ['/customer/show/1', self::ROLE_USER];
        yield ['/customer/show/1', self::ROLE_ADMIN];
        yield ['/customer/show/1', self::ROLE_SUPER_ADMIN];
        yield ['/customer/pdf', self::ROLE_USER];
        yield ['/customer/pdf', self::ROLE_ADMIN];
        yield ['/customer/pdf', self::ROLE_SUPER_ADMIN];
        yield ['/customer/excel', self::ROLE_USER];
        yield ['/customer/excel', self::ROLE_ADMIN];
        yield ['/customer/excel', self::ROLE_SUPER_ADMIN];
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function addEntities(): void
    {
        if (!$this->entity instanceof Customer) {
            $this->entity = new Customer();
            $this->entity->setCompany('Test Company');
            $this->addEntity($this->entity);
        }
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteEntities(): void
    {
        $this->entity = $this->deleteEntity($this->entity);
    }
}
