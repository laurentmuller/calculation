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
use App\Entity\GlobalMargin;
use App\Tests\EntityTrait\GlobalMarginTrait;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(AbstractController::class)]
#[CoversClass(AbstractEntityController::class)]
#[CoversClass(GlobalMarginController::class)]
class GlobalMarginControllerTest extends EntityControllerTestCase
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
     * @throws ORMException
     */
    public function testEdit(): void
    {
        $this->deleteEntitiesByClass(GlobalMargin::class);
        $this->addEntities();
        $uri = '/globalmargin/edit';
        $this->checkEditEntity($uri, id: 'common.button_ok');
    }

    /**
     * @throws ORMException
     */
    public function testExcelEmpty(): void
    {
        $this->checkUriWithEmptyEntity('/globalmargin/excel', GlobalMargin::class);
    }

    /**
     * @throws ORMException
     */
    public function testPdfEmpty(): void
    {
        $this->checkUriWithEmptyEntity('/globalmargin/pdf', GlobalMargin::class);
    }

    /**
     * @throws ORMException
     */
    protected function addEntities(): void
    {
        $this->getGlobalMargin();
    }

    /**
     * @throws ORMException
     */
    protected function deleteEntities(): void
    {
        $this->deleteGlobalMargin();
    }
}
