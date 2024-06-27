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
use App\Controller\CategoryController;
use App\Tests\EntityTrait\CategoryTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(AbstractController::class)]
#[CoversClass(AbstractEntityController::class)]
#[CoversClass(CategoryController::class)]
class CategoryControllerTest extends ControllerTestCase
{
    use CategoryTrait;

    public static function getRoutes(): \Iterator
    {
        yield ['/category', self::ROLE_USER];
        yield ['/category', self::ROLE_ADMIN];
        yield ['/category', self::ROLE_SUPER_ADMIN];
        yield ['/category/add', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/category/add', self::ROLE_ADMIN];
        yield ['/category/add', self::ROLE_SUPER_ADMIN];
        yield ['/category/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/category/edit/1', self::ROLE_ADMIN];
        yield ['/category/edit/1', self::ROLE_SUPER_ADMIN];
        yield ['/category/clone/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/category/clone/1', self::ROLE_ADMIN];
        yield ['/category/clone/1', self::ROLE_SUPER_ADMIN];
        yield ['/category/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/category/delete/1', self::ROLE_ADMIN];
        yield ['/category/delete/1', self::ROLE_SUPER_ADMIN];
        yield ['/category/show/1', self::ROLE_USER];
        yield ['/category/show/1', self::ROLE_ADMIN];
        yield ['/category/show/1', self::ROLE_SUPER_ADMIN];
        yield ['/category/pdf', self::ROLE_USER];
        yield ['/category/pdf', self::ROLE_ADMIN];
        yield ['/category/pdf', self::ROLE_SUPER_ADMIN];
        yield ['/category/excel', self::ROLE_USER];
        yield ['/category/excel', self::ROLE_ADMIN];
        yield ['/category/excel', self::ROLE_SUPER_ADMIN];
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function addEntities(): void
    {
        $this->getCategory();
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteEntities(): void
    {
        $this->deleteCategory();
        $this->deleteGroup();
    }
}
