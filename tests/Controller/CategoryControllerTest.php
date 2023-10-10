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

use App\Controller\CategoryController;
use App\Tests\EntityTrait\CategoryTrait;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(CategoryController::class)]
class CategoryControllerTest extends AbstractControllerTestCase
{
    use CategoryTrait;

    public static function getRoutes(): array
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

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function addEntities(): void
    {
        $this->getCategory($this->getGroup());
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
