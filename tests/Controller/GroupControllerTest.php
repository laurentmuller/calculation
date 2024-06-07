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
use App\Controller\GroupController;
use App\Tests\EntityTrait\GroupTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(AbstractController::class)]
#[CoversClass(AbstractEntityController::class)]
#[CoversClass(GroupController::class)]
class GroupControllerTest extends AbstractControllerTestCase
{
    use GroupTrait;

    public static function getRoutes(): \Iterator
    {
        yield ['/group', self::ROLE_USER];
        yield ['/group', self::ROLE_ADMIN];
        yield ['/group', self::ROLE_SUPER_ADMIN];
        yield ['/group/add', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/group/add', self::ROLE_ADMIN];
        yield ['/group/add', self::ROLE_SUPER_ADMIN];
        yield ['/group/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/group/edit/1', self::ROLE_ADMIN];
        yield ['/group/edit/1', self::ROLE_SUPER_ADMIN];
        yield ['/group/clone/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/group/clone/1', self::ROLE_ADMIN];
        yield ['/group/clone/1', self::ROLE_SUPER_ADMIN];
        yield ['/group/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/group/delete/1', self::ROLE_ADMIN];
        yield ['/group/delete/1', self::ROLE_SUPER_ADMIN];
        yield ['/group/show/1', self::ROLE_USER];
        yield ['/group/show/1', self::ROLE_ADMIN];
        yield ['/group/show/1', self::ROLE_SUPER_ADMIN];
        yield ['/group/pdf', self::ROLE_USER];
        yield ['/group/pdf', self::ROLE_ADMIN];
        yield ['/group/pdf', self::ROLE_SUPER_ADMIN];
        yield ['/group/excel', self::ROLE_USER];
        yield ['/group/excel', self::ROLE_ADMIN];
        yield ['/group/excel', self::ROLE_SUPER_ADMIN];
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function addEntities(): void
    {
        $this->getGroup();
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteEntities(): void
    {
        $this->deleteGroup();
    }
}
