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
use App\Controller\GlobalMarginController;
use App\Tests\EntityTrait\GlobalMarginTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(AbstractController::class)]
#[CoversClass(AbstractEntityController::class)]
#[CoversClass(GlobalMarginController::class)]
class GlobalMarginControllerTest extends ControllerTestCase
{
    use GlobalMarginTrait;

    public static function getRoutes(): \Iterator
    {
        yield ['/globalmargin', self::ROLE_USER];
        yield ['/globalmargin', self::ROLE_ADMIN];
        yield ['/globalmargin', self::ROLE_SUPER_ADMIN];
        yield ['/globalmargin/edit', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/globalmargin/edit', self::ROLE_ADMIN];
        yield ['/globalmargin/edit', self::ROLE_SUPER_ADMIN];
        yield ['/globalmargin/show/1', self::ROLE_USER];
        yield ['/globalmargin/show/1', self::ROLE_ADMIN];
        yield ['/globalmargin/show/1', self::ROLE_SUPER_ADMIN];
        yield ['/globalmargin/pdf', self::ROLE_USER];
        yield ['/globalmargin/pdf', self::ROLE_ADMIN];
        yield ['/globalmargin/pdf', self::ROLE_SUPER_ADMIN];
        yield ['/globalmargin/excel', self::ROLE_USER];
        yield ['/globalmargin/excel', self::ROLE_ADMIN];
        yield ['/globalmargin/excel', self::ROLE_SUPER_ADMIN];
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function addEntities(): void
    {
        $this->getGlobalMargin();
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteEntities(): void
    {
        $this->deleteGlobalMargin();
    }
}
